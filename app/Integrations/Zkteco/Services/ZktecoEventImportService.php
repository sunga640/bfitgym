<?php

namespace App\Integrations\Zkteco\Services;

use App\Integrations\Zkteco\Repositories\ZktecoConnectionRepository;
use App\Integrations\Zkteco\Repositories\ZktecoEventRepository;
use App\Integrations\Zkteco\ZktecoProviderManager;
use App\Models\User;
use App\Models\ZktecoConnection;
use App\Models\ZktecoDevice;
use App\Models\ZktecoMemberMap;
use App\Models\ZktecoSyncRun;
use App\Support\AccessLogger;
use Illuminate\Support\Carbon;

class ZktecoEventImportService
{
    public function __construct(
        private readonly ZktecoProviderManager $providers,
        private readonly ZktecoConnectionRepository $connections,
        private readonly ZktecoEventRepository $events,
        private readonly AccessLogger $logger,
    ) {
    }

    /**
     * @return array{total:int, imported:int, skipped:int, failed:int}
     */
    public function syncBranch(ZktecoConnection $connection, ?Carbon $since = null, ?User $actor = null): array
    {
        $run = $this->connections->startSyncRun(
            connection: $connection,
            run_type: ZktecoSyncRun::TYPE_EVENT_SYNC,
            triggered_by_user_id: $actor?->id,
        );

        $since = $since ?: ($connection->last_event_sync_at?->copy()->subMinutes(2) ?? now()->subHours(6));
        $until = now();

        $provider = $this->providers->resolve($connection);
        $items = $provider->pullEvents($connection, $since, $until);

        $device_ids = ZktecoDevice::query()
            ->withoutBranchScope()
            ->where('zkteco_connection_id', $connection->id)
            ->pluck('id', 'remote_device_id')
            ->all();

        $member_ids = ZktecoMemberMap::query()
            ->withoutBranchScope()
            ->where('zkteco_connection_id', $connection->id)
            ->whereNotNull('remote_personnel_id')
            ->pluck('member_id', 'remote_personnel_id')
            ->all();

        $total = count($items);
        $imported = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($items as $item) {
            try {
                $remote_event_id = $item['remote_event_id'] ?? null;
                $fingerprint = $this->fingerprint($connection, $item);

                if ($this->events->exists($connection, $remote_event_id, $fingerprint)) {
                    $skipped++;
                    continue;
                }

                $remote_device_id = $item['remote_device_id'] ?? null;
                $remote_personnel_id = $item['remote_personnel_id'] ?? null;

                $device_id = $remote_device_id && isset($device_ids[$remote_device_id])
                    ? (int) $device_ids[$remote_device_id]
                    : null;

                $member_id = $remote_personnel_id && isset($member_ids[$remote_personnel_id])
                    ? (int) $member_ids[$remote_personnel_id]
                    : null;

                $this->events->create($connection, [
                    'zkteco_device_id' => $device_id,
                    'member_id' => $member_id,
                    'remote_event_id' => $remote_event_id,
                    'event_fingerprint' => $fingerprint,
                    'remote_personnel_id' => $remote_personnel_id,
                    'direction' => $item['direction'] ?? 'unknown',
                    'event_status' => $item['event_status'] ?? null,
                    'occurred_at' => $item['occurred_at'] ?? now(),
                    'matched_member' => $member_id !== null,
                    'raw_payload' => $item['raw_payload'] ?? $item,
                ]);

                $imported++;
            } catch (\Throwable) {
                $failed++;
            }
        }

        $status = $this->resolveStatus($imported, $failed);

        $connection->update([
            'last_event_sync_at' => $imported > 0 ? now() : $connection->last_event_sync_at,
            'status' => $status === ZktecoSyncRun::STATUS_FAILED ? ZktecoConnection::STATUS_ERROR : ZktecoConnection::STATUS_CONNECTED,
            'last_error' => $failed > 0 ? "Event import had {$failed} failure(s)." : null,
        ]);

        $this->connections->finishSyncRun(
            run: $run,
            status: $status,
            records_total: $total,
            records_success: $imported,
            records_failed: $failed,
            error_message: $failed > 0 ? "Event import had {$failed} failure(s)." : null
        );

        $this->logger->info('zkteco_event_sync_completed', [
            'branch_id' => $connection->branch_id,
            'connection_id' => $connection->id,
            'actor_id' => $actor?->id,
            'total' => $total,
            'imported' => $imported,
            'skipped' => $skipped,
            'failed' => $failed,
            'since' => $since->toIso8601String(),
            'until' => $until->toIso8601String(),
        ]);

        return [
            'total' => $total,
            'imported' => $imported,
            'skipped' => $skipped,
            'failed' => $failed,
        ];
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function fingerprint(ZktecoConnection $connection, array $item): string
    {
        return sha1(json_encode([
            'connection' => $connection->id,
            'device' => $item['remote_device_id'] ?? null,
            'personnel' => $item['remote_personnel_id'] ?? null,
            'occurred_at' => isset($item['occurred_at']) && $item['occurred_at'] instanceof Carbon
                ? $item['occurred_at']->toIso8601String()
                : (string) ($item['occurred_at'] ?? ''),
            'direction' => $item['direction'] ?? 'unknown',
        ], JSON_THROW_ON_ERROR));
    }

    private function resolveStatus(int $success, int $failed): string
    {
        if ($failed === 0) {
            return ZktecoSyncRun::STATUS_SUCCESS;
        }

        if ($success > 0) {
            return ZktecoSyncRun::STATUS_PARTIAL;
        }

        return ZktecoSyncRun::STATUS_FAILED;
    }
}

