<?php

namespace App\Services\Attendance;

use App\Models\AccessControlDevice;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class AttendanceReportService
{
    public function query(array $filters): Builder
    {
        [$from, $to] = $this->resolveDateRange($filters);

        $integration_type = $this->normalizeIntegrationType($filters['integration_type'] ?? null);
        $branch_id = $this->toNullableInt($filters['branch_id'] ?? null);

        $hikvision_query = $this->hikvisionQuery($from, $to, $branch_id);
        $zkteco_legacy_query = $this->zktecoLegacyQuery($from, $to, $branch_id);
        $zkteco_agent_query = $this->zktecoAgentQuery($from, $to, $branch_id);
        $zkteco_query = $this->unionQueries([$zkteco_legacy_query, $zkteco_agent_query]);

        $source_query = match ($integration_type) {
            AccessControlDevice::INTEGRATION_HIKVISION => $hikvision_query,
            AccessControlDevice::INTEGRATION_ZKTECO => $zkteco_query,
            default => $this->unionQueries([$hikvision_query, $zkteco_legacy_query, $zkteco_agent_query]),
        };

        $query = DB::query()->fromSub($source_query, 'attendance_events');

        $provider = trim((string) ($filters['provider'] ?? ''));
        if ($provider !== '') {
            $query->whereIn('provider', AccessControlDevice::providerAliases($provider));
        }

        $direction = trim((string) ($filters['direction'] ?? ''));
        if ($direction !== '') {
            $query->where('direction', $direction);
        }

        $device = $this->parseDeviceKey($filters['device'] ?? null);
        if ($device !== null) {
            $query->where('integration_type', $device['integration_type'])
                ->where('source_device_key', $device['source_device_key']);
        }

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function (Builder $inner) use ($search) {
                $inner->where('source_device_name', 'like', '%' . $search . '%')
                    ->orWhere('source_agent_name', 'like', '%' . $search . '%')
                    ->orWhere('device_user_id', 'like', '%' . $search . '%')
                    ->orWhere('subject_code', 'like', '%' . $search . '%')
                    ->orWhere('subject_first_name', 'like', '%' . $search . '%')
                    ->orWhere('subject_last_name', 'like', '%' . $search . '%')
                    ->orWhere('event_uid', 'like', '%' . $search . '%');
            });
        }

        return $query;
    }

    /**
     * @return array{total:int,hikvision:int,zkteco:int}
     */
    public function summary(array $filters): array
    {
        $query = $this->query($filters);

        $total = (int) (clone $query)->count();

        $integration_counts = (clone $query)
            ->select('integration_type', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('integration_type')
            ->pluck('aggregate', 'integration_type')
            ->all();

        return [
            'total' => $total,
            'hikvision' => (int) ($integration_counts[AccessControlDevice::INTEGRATION_HIKVISION] ?? 0),
            'zkteco' => (int) ($integration_counts[AccessControlDevice::INTEGRATION_ZKTECO] ?? 0),
        ];
    }

    private function hikvisionQuery(Carbon $from, Carbon $to, ?int $branch_id): Builder
    {
        return DB::table('access_logs as l')
            ->leftJoin('access_control_devices as d', 'd.id', '=', 'l.access_control_device_id')
            ->leftJoin('access_control_agents as a', 'a.id', '=', 'd.access_control_agent_id')
            ->leftJoin('access_identities as ai', 'ai.id', '=', 'l.access_identity_id')
            ->leftJoin('members as m', function ($join) {
                $join->on('m.id', '=', 'l.subject_id')
                    ->where('l.subject_type', '=', 'member');
            })
            ->leftJoin('users as u', function ($join) {
                $join->on('u.id', '=', 'l.subject_id')
                    ->where('l.subject_type', '=', 'staff');
            })
            ->when($branch_id, fn(Builder $query) => $query->where('l.branch_id', $branch_id))
            ->whereBetween('l.event_timestamp', [$from, $to])
            ->select([
                DB::raw("'access_logs' as source_table"),
                'l.id as source_id',
                'l.branch_id',
                'l.integration_type',
                'l.provider',
                'l.access_control_device_id as source_device_id',
                'd.name as source_device_name',
                DB::raw("CONCAT('hikvision:', l.access_control_device_id) as source_device_key"),
                'd.access_control_agent_id as source_agent_id',
                'a.name as source_agent_name',
                'l.subject_type',
                'l.subject_id',
                DB::raw("CASE WHEN l.subject_type = 'member' THEN m.first_name ELSE u.name END as subject_first_name"),
                DB::raw("CASE WHEN l.subject_type = 'member' THEN m.last_name ELSE NULL END as subject_last_name"),
                DB::raw("CASE WHEN l.subject_type = 'member' THEN m.member_no ELSE u.email END as subject_code"),
                'ai.device_user_id',
                'l.direction',
                'l.event_timestamp',
                'l.device_event_uid as event_uid',
                DB::raw('NULL as event_status'),
            ]);
    }

    private function zktecoLegacyQuery(Carbon $from, Carbon $to, ?int $branch_id): Builder
    {
        $zkteco_provider = AccessControlDevice::PROVIDER_ZKTECO_ZKBIO;

        return DB::table('zkteco_access_events as z')
            ->leftJoin('zkteco_devices as zd', 'zd.id', '=', 'z.zkteco_device_id')
            ->leftJoin('zkteco_connections as zc', 'zc.id', '=', 'z.zkteco_connection_id')
            ->leftJoin('members as m', 'm.id', '=', 'z.member_id')
            ->when($branch_id, fn(Builder $query) => $query->where('z.branch_id', $branch_id))
            ->whereBetween('z.occurred_at', [$from, $to])
            ->select([
                DB::raw("'zkteco_access_events' as source_table"),
                'z.id as source_id',
                'z.branch_id',
                DB::raw("'zkteco' as integration_type"),
                DB::raw("CASE WHEN zc.provider = 'zkbio_api' OR zc.provider = 'zkbio_platform' THEN '{$zkteco_provider}' ELSE zc.provider END as provider"),
                'z.zkteco_device_id as source_device_id',
                DB::raw('COALESCE(zd.remote_name, zd.remote_device_id) as source_device_name'),
                DB::raw("CONCAT('zkteco:', z.zkteco_device_id) as source_device_key"),
                DB::raw('NULL as source_agent_id'),
                DB::raw("'ZKBio CVAccess' as source_agent_name"),
                DB::raw("CASE WHEN z.member_id IS NULL THEN 'unknown' ELSE 'member' END as subject_type"),
                'z.member_id as subject_id',
                'm.first_name as subject_first_name',
                'm.last_name as subject_last_name',
                'm.member_no as subject_code',
                'z.remote_personnel_id as device_user_id',
                'z.direction',
                'z.occurred_at as event_timestamp',
                DB::raw('COALESCE(z.remote_event_id, z.event_fingerprint) as event_uid'),
                'z.event_status',
            ]);
    }

    private function zktecoAgentQuery(Carbon $from, Carbon $to, ?int $branch_id): Builder
    {
        $provider = AccessControlDevice::PROVIDER_ZKTECO_AGENT;

        return DB::table('cvsecurity_events as e')
            ->leftJoin('cvsecurity_connections as c', 'c.id', '=', 'e.cvsecurity_connection_id')
            ->leftJoin('cvsecurity_agents as a', 'a.id', '=', 'e.agent_id')
            ->leftJoin('members as m', 'm.id', '=', 'e.member_id')
            ->when($branch_id, fn(Builder $query) => $query->where('e.branch_id', $branch_id))
            ->whereBetween('e.occurred_at', [$from, $to])
            ->select([
                DB::raw("'cvsecurity_events' as source_table"),
                'e.id as source_id',
                'e.branch_id',
                DB::raw("'zkteco' as integration_type"),
                DB::raw("'{$provider}' as provider"),
                'e.device_id as source_device_id',
                DB::raw("COALESCE(NULLIF(e.device_id, ''), NULLIF(e.reader_id, ''), NULLIF(e.door_id, ''), '-') as source_device_name"),
                DB::raw("CASE WHEN e.device_id IS NULL OR e.device_id = '' THEN NULL ELSE CONCAT('zkteco:cvsecurity:', e.cvsecurity_connection_id, ':', e.device_id) END as source_device_key"),
                'e.agent_id as source_agent_id',
                DB::raw("COALESCE(NULLIF(a.display_name, ''), NULLIF(c.agent_label, ''), NULLIF(c.name, ''), 'CVSecurity Agent') as source_agent_name"),
                DB::raw("CASE WHEN e.member_id IS NULL THEN 'unknown' ELSE 'member' END as subject_type"),
                'e.member_id as subject_id',
                'm.first_name as subject_first_name',
                'm.last_name as subject_last_name',
                'm.member_no as subject_code',
                DB::raw("COALESCE(NULLIF(e.external_person_id, ''), m.member_no) as device_user_id"),
                DB::raw("CASE
                    WHEN LOWER(COALESCE(e.direction, '')) IN ('in', 'entry', 'checkin', 'check_in', 'enter', 'entry_granted') THEN 'in'
                    WHEN LOWER(COALESCE(e.direction, '')) IN ('out', 'exit', 'checkout', 'check_out', 'leave', 'exit_granted') THEN 'out'
                    ELSE 'unknown'
                END as direction"),
                'e.occurred_at as event_timestamp',
                DB::raw('COALESCE(e.external_event_id, e.dedupe_hash) as event_uid'),
                'e.processing_status as event_status',
            ]);
    }

    /**
     * @param array<int, Builder> $queries
     */
    private function unionQueries(array $queries): Builder
    {
        $base = array_shift($queries);
        if (!$base instanceof Builder) {
            throw new \InvalidArgumentException('At least one query is required for union.');
        }

        foreach ($queries as $query) {
            $base->unionAll($query);
        }

        return $base;
    }

    /**
     * @return array{0:Carbon,1:Carbon}
     */
    private function resolveDateRange(array $filters): array
    {
        $from = isset($filters['date_from']) && trim((string) $filters['date_from']) !== ''
            ? Carbon::parse((string) $filters['date_from'])->startOfDay()
            : now()->startOfMonth();

        $to = isset($filters['date_to']) && trim((string) $filters['date_to']) !== ''
            ? Carbon::parse((string) $filters['date_to'])->endOfDay()
            : now()->endOfMonth();

        if ($from->gt($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        return [$from, $to];
    }

    private function normalizeIntegrationType(mixed $integration_type): ?string
    {
        $value = trim((string) $integration_type);

        return match ($value) {
            AccessControlDevice::INTEGRATION_HIKVISION => AccessControlDevice::INTEGRATION_HIKVISION,
            AccessControlDevice::INTEGRATION_ZKTECO => AccessControlDevice::INTEGRATION_ZKTECO,
            default => null,
        };
    }

    private function toNullableInt(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && ctype_digit($value)) {
            return (int) $value;
        }

        return null;
    }

    /**
     * @return array{integration_type:string,source_device_key:string}|null
     */
    private function parseDeviceKey(mixed $value): ?array
    {
        $raw = trim((string) $value);
        if ($raw === '' || !str_contains($raw, ':')) {
            return null;
        }

        [$integration_type, $rest] = explode(':', $raw, 2);

        $integration_type = $this->normalizeIntegrationType($integration_type);
        if ($integration_type === null) {
            return null;
        }

        if (trim($rest) === '') {
            return null;
        }

        return [
            'integration_type' => $integration_type,
            'source_device_key' => $raw,
        ];
    }
}
