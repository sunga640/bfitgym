<?php

namespace App\Services\CvSecurity;

use App\Models\CvSecurityAgent;
use App\Models\CvSecurityConnection;
use App\Models\CvSecurityEvent;
use App\Models\CvSecurityMemberSyncItem;
use App\Models\CvSecuritySyncState;
use App\Models\Member;
use Illuminate\Support\Facades\DB;

class AgentBridgeService
{
    public function __construct(
        private readonly PairingService $pairing_service,
        private readonly ActivityLogger $activity_logger,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function pair(array $payload, ?string $ip = null): array
    {
        $result = $this->pairing_service->claim(
            pairing_token: (string) $payload['pairing_token'],
            agent_payload: [
                'agent_uuid' => $payload['agent_uuid'] ?? null,
                'agent_name' => $payload['agent_name'] ?? 'Local Agent',
                'os' => $payload['os'] ?? null,
                'app_version' => $payload['app_version'] ?? null,
            ],
            connection_overrides: [
                'cv_base_url' => $payload['cv_base_url'] ?? null,
                'cv_port' => $payload['cv_port'] ?? null,
                'cv_username' => $payload['cv_username'] ?? null,
                'cv_password' => $payload['cv_password'] ?? null,
                'cv_api_token' => $payload['cv_api_token'] ?? null,
                'poll_interval_seconds' => $payload['poll_interval_seconds'] ?? null,
                'timezone' => $payload['timezone'] ?? null,
            ],
            ip: $ip,
        );

        /** @var CvSecurityConnection $connection */
        $connection = $result['connection'];
        /** @var CvSecurityAgent $agent */
        $agent = $result['agent'];

        return [
            'connection_id' => $connection->id,
            'branch_id' => $connection->branch_id,
            'agent_uuid' => $agent->uuid,
            'agent_token' => $result['agent_token'],
            'poll_interval_seconds' => $connection->poll_interval_seconds,
            'server_time' => now()->toIso8601String(),
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function heartbeat(CvSecurityAgent $agent, array $payload, ?string $ip = null): array
    {
        $connection = $agent->connection;

        $agent->update([
            'status' => CvSecurityAgent::STATUS_ACTIVE,
            'last_seen_at' => now(),
            'last_heartbeat_at' => now(),
            'last_ip' => $ip ?: $agent->last_ip,
            'app_version' => $payload['app_version'] ?? $agent->app_version,
            'os' => $payload['os'] ?? $agent->os,
        ]);

        $connection->update([
            'status' => CvSecurityConnection::STATUS_CONNECTED,
            'pairing_status' => CvSecurityConnection::PAIRING_PAIRED,
            'agent_status' => 'online',
            'last_heartbeat_at' => now(),
        ]);

        return [
            'ok' => true,
            'server_time' => now()->toIso8601String(),
            'requested_actions' => [
                'test_connection' => (bool) $connection->agent_test_requested,
                'sync_members' => (bool) $connection->agent_sync_requested,
                'pull_events' => (bool) $connection->agent_event_pull_requested,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function config(CvSecurityAgent $agent): array
    {
        $connection = $agent->connection->fresh();

        $pending_sync_items = CvSecurityMemberSyncItem::query()
            ->where('cvsecurity_connection_id', $connection->id)
            ->whereIn('status', [CvSecurityMemberSyncItem::STATUS_PENDING, CvSecurityMemberSyncItem::STATUS_RETRY])
            ->count();

        return [
            'connection' => [
                'id' => $connection->id,
                'name' => $connection->name,
                'branch_id' => $connection->branch_id,
                'poll_interval_seconds' => $connection->poll_interval_seconds,
                'timezone' => $connection->timezone ?: config('app.timezone'),
                'cvsecurity' => [
                    'base_url' => $connection->cv_base_url,
                    'port' => $connection->cv_port,
                    'username' => $connection->cv_username,
                    'password' => $connection->cv_password,
                    'api_token' => $connection->cv_api_token,
                ],
            ],
            'status' => [
                'connection_status' => $connection->status,
                'agent_status' => $connection->agent_status,
                'cvsecurity_status' => $connection->cvsecurity_status,
                'last_sync_at' => $connection->last_sync_at?->toIso8601String(),
                'last_event_at' => $connection->last_event_at?->toIso8601String(),
                'last_error' => $connection->last_error,
            ],
            'requested_actions' => [
                'test_connection' => (bool) $connection->agent_test_requested,
                'sync_members' => (bool) $connection->agent_sync_requested,
                'pull_events' => (bool) $connection->agent_event_pull_requested,
            ],
            'metrics' => [
                'pending_member_sync_items' => $pending_sync_items,
            ],
            'server_time' => now()->toIso8601String(),
        ];
    }

    /**
     * @return array{items:array<int, array<string,mixed>>,count:int}
     */
    public function claimMemberSyncItems(CvSecurityAgent $agent, int $limit = 100): array
    {
        $connection_id = $agent->cvsecurity_connection_id;
        $limit = max(1, min(500, $limit));

        return DB::transaction(function () use ($agent, $connection_id, $limit) {
            $items = CvSecurityMemberSyncItem::query()
                ->where('cvsecurity_connection_id', $connection_id)
                ->whereIn('status', [CvSecurityMemberSyncItem::STATUS_PENDING, CvSecurityMemberSyncItem::STATUS_RETRY])
                ->where(function ($q) {
                    $q->whereNull('available_at')->orWhere('available_at', '<=', now());
                })
                ->orderBy('id')
                ->limit($limit)
                ->get();

            if ($items->isEmpty()) {
                return ['items' => [], 'count' => 0];
            }

            CvSecurityMemberSyncItem::query()
                ->whereIn('id', $items->pluck('id')->all())
                ->update([
                    'status' => CvSecurityMemberSyncItem::STATUS_PROCESSING,
                    'claimed_by_agent_id' => $agent->id,
                    'claimed_at' => now(),
                ]);

            $mapped = $items->map(function (CvSecurityMemberSyncItem $item) {
                return [
                    'sync_item_id' => $item->id,
                    'member_id' => $item->member_id,
                    'external_person_id' => $item->external_person_id,
                    'action' => $item->sync_action,
                    'desired_state' => $item->desired_state,
                    'payload' => $item->payload ?? [],
                ];
            })->values()->all();

            return [
                'items' => $mapped,
                'count' => count($mapped),
            ];
        });
    }

    /**
     * @param array<int, array<string, mixed>> $results
     * @return array{processed:int,succeeded:int,failed:int}
     */
    public function applyMemberSyncResults(CvSecurityAgent $agent, array $results): array
    {
        $processed = 0;
        $succeeded = 0;
        $failed = 0;
        $connection = $agent->connection;

        foreach ($results as $result) {
            $sync_item_id = (int) ($result['sync_item_id'] ?? 0);
            if ($sync_item_id <= 0) {
                continue;
            }

            /** @var CvSecurityMemberSyncItem|null $item */
            $item = CvSecurityMemberSyncItem::query()
                ->where('id', $sync_item_id)
                ->where('cvsecurity_connection_id', $agent->cvsecurity_connection_id)
                ->first();

            if (!$item) {
                continue;
            }

            $status = (string) ($result['status'] ?? 'failed');
            $retryable = (bool) ($result['retryable'] ?? false);

            if ($status === 'done') {
                $item->update([
                    'status' => CvSecurityMemberSyncItem::STATUS_DONE,
                    'processed_at' => now(),
                    'last_error' => null,
                    'last_error_at' => null,
                    'result' => is_array($result['result'] ?? null) ? $result['result'] : null,
                ]);

                $succeeded++;
            } else {
                $next_attempt = (int) $item->attempts + 1;
                $new_status = $retryable && $next_attempt < 10
                    ? CvSecurityMemberSyncItem::STATUS_RETRY
                    : CvSecurityMemberSyncItem::STATUS_FAILED;

                $item->update([
                    'status' => $new_status,
                    'attempts' => $next_attempt,
                    'processed_at' => now(),
                    'available_at' => $new_status === CvSecurityMemberSyncItem::STATUS_RETRY
                        ? now()->addSeconds(min(120, 5 * $next_attempt))
                        : null,
                    'last_error' => $this->safeError((string) ($result['error'] ?? 'Sync failed.')),
                    'last_error_at' => now(),
                    'result' => is_array($result['result'] ?? null) ? $result['result'] : null,
                ]);

                $failed++;
            }

            $processed++;
        }

        $pending = CvSecurityMemberSyncItem::query()
            ->where('cvsecurity_connection_id', $connection->id)
            ->whereIn('status', [CvSecurityMemberSyncItem::STATUS_PENDING, CvSecurityMemberSyncItem::STATUS_RETRY])
            ->count();

        $failed_total = CvSecurityMemberSyncItem::query()
            ->where('cvsecurity_connection_id', $connection->id)
            ->where('status', CvSecurityMemberSyncItem::STATUS_FAILED)
            ->count();

        CvSecuritySyncState::query()->updateOrCreate(
            ['cvsecurity_connection_id' => $connection->id],
            [
                'branch_id' => $connection->branch_id,
                'last_member_sync_at' => now(),
                'last_success_at' => $succeeded > 0 ? now() : null,
                'pending_members_count' => $pending,
                'failed_members_count' => $failed_total,
            ]
        );

        if ($succeeded > 0) {
            $connection->update([
                'last_sync_at' => now(),
                'status' => CvSecurityConnection::STATUS_CONNECTED,
                'agent_sync_requested' => false,
            ]);
        }

        if ($failed > 0) {
            $connection->update([
                'status' => CvSecurityConnection::STATUS_ERROR,
                'last_error' => 'One or more member sync items failed.',
                'last_error_at' => now(),
            ]);
        }

        return [
            'processed' => $processed,
            'succeeded' => $succeeded,
            'failed' => $failed,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $events
     * @return array{received:int,stored:int,duplicates:int}
     */
    public function ingestEvents(CvSecurityAgent $agent, array $events): array
    {
        $connection = $agent->connection;
        $received = count($events);
        $stored = 0;
        $duplicates = 0;

        foreach ($events as $event) {
            $dedupe_hash = $this->buildEventDedupeHash($connection, $event);

            $member_no = trim((string) ($event['external_person_id'] ?? ''));
            $member_id = null;
            if ($member_no !== '') {
                $member_id = Member::query()
                    ->withoutBranchScope()
                    ->where('branch_id', $connection->branch_id)
                    ->where('member_no', $member_no)
                    ->value('id');
            }

            $row = CvSecurityEvent::query()->firstOrCreate(
                [
                    'cvsecurity_connection_id' => $connection->id,
                    'dedupe_hash' => $dedupe_hash,
                ],
                [
                    'branch_id' => $connection->branch_id,
                    'agent_id' => $agent->id,
                    'member_id' => $member_id,
                    'external_event_id' => $this->nullableString($event, 'external_event_id'),
                    'external_person_id' => $member_no !== '' ? $member_no : null,
                    'event_type' => $this->nullableString($event, 'event_type') ?: 'access_event',
                    'direction' => $this->nullableString($event, 'direction'),
                    'occurred_at' => $event['occurred_at'] ?? now()->toIso8601String(),
                    'device_id' => $this->nullableString($event, 'device_id'),
                    'door_id' => $this->nullableString($event, 'door_id'),
                    'reader_id' => $this->nullableString($event, 'reader_id'),
                    'processing_status' => 'received',
                    'raw_payload' => is_array($event['raw_payload'] ?? null) ? $event['raw_payload'] : $event,
                    'received_at' => now(),
                ]
            );

            if ($row->wasRecentlyCreated) {
                $stored++;
            } else {
                $duplicates++;
            }
        }

        $connection->update([
            'last_event_at' => $stored > 0 ? now() : $connection->last_event_at,
            'agent_event_pull_requested' => false,
        ]);

        CvSecuritySyncState::query()->updateOrCreate(
            ['cvsecurity_connection_id' => $connection->id],
            [
                'branch_id' => $connection->branch_id,
                'last_event_pull_at' => now(),
            ]
        );

        if ($stored > 0) {
            $this->activity_logger->log(
                connection: $connection,
                level: 'info',
                event: 'events_ingested',
                message: 'Access events received from local agent.',
                context: [
                    'stored' => $stored,
                    'duplicates' => $duplicates,
                ],
                agent: $agent,
            );
        }

        return [
            'received' => $received,
            'stored' => $stored,
            'duplicates' => $duplicates,
        ];
    }

    /**
     * @param array<string, mixed> $status_payload
     * @return array<string, mixed>
     */
    public function reportStatus(CvSecurityAgent $agent, array $status_payload): array
    {
        $connection = $agent->connection;

        $cv_status = strtolower(trim((string) ($status_payload['cvsecurity_status'] ?? 'unknown')));
        if (!in_array($cv_status, ['reachable', 'unreachable', 'unknown'], true)) {
            $cv_status = 'unknown';
        }

        $last_error = $this->nullableString($status_payload, 'last_error');

        $connection->update([
            'status' => $cv_status === 'unreachable' ? CvSecurityConnection::STATUS_ERROR : CvSecurityConnection::STATUS_CONNECTED,
            'cvsecurity_status' => $cv_status,
            'agent_status' => 'online',
            'last_error' => $last_error,
            'last_error_at' => $last_error ? now() : null,
            'agent_test_requested' => $this->boolFromPayload($status_payload, 'ack_test_connection') ? false : $connection->agent_test_requested,
            'agent_sync_requested' => $this->boolFromPayload($status_payload, 'ack_sync_members') ? false : $connection->agent_sync_requested,
            'agent_event_pull_requested' => $this->boolFromPayload($status_payload, 'ack_pull_events') ? false : $connection->agent_event_pull_requested,
        ]);

        $agent->update([
            'last_seen_at' => now(),
            'last_heartbeat_at' => now(),
            'last_error' => $last_error,
            'last_error_at' => $last_error ? now() : null,
            'metadata' => is_array($status_payload['metadata'] ?? null) ? $status_payload['metadata'] : null,
        ]);

        if ($this->boolFromPayload($status_payload, 'ack_test_connection')) {
            $connection->update([
                'last_tested_at' => now(),
                'last_test_result' => [
                    'ok' => $cv_status === 'reachable',
                    'message' => $last_error ?: 'Connection test completed.',
                ],
            ]);
        }

        if ($last_error) {
            $this->activity_logger->log(
                connection: $connection,
                level: 'error',
                event: 'agent_reported_error',
                message: $last_error,
                context: is_array($status_payload['metadata'] ?? null) ? $status_payload['metadata'] : null,
                agent: $agent,
            );
        }

        return [
            'ok' => true,
            'server_time' => now()->toIso8601String(),
            'connection_status' => $connection->fresh()->status,
        ];
    }

    /**
     * @param array<string, mixed> $event
     */
    private function buildEventDedupeHash(CvSecurityConnection $connection, array $event): string
    {
        $external_event_id = trim((string) ($event['external_event_id'] ?? ''));
        if ($external_event_id !== '') {
            return hash('sha256', $connection->id . '|ext|' . $external_event_id);
        }

        return hash('sha256', implode('|', [
            $connection->id,
            trim((string) ($event['external_person_id'] ?? '')),
            trim((string) ($event['event_type'] ?? 'access_event')),
            trim((string) ($event['occurred_at'] ?? '')),
            trim((string) ($event['device_id'] ?? '')),
            trim((string) ($event['door_id'] ?? '')),
            trim((string) ($event['reader_id'] ?? '')),
        ]));
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function nullableString(array $payload, string $key): ?string
    {
        if (!array_key_exists($key, $payload)) {
            return null;
        }

        $value = trim((string) $payload[$key]);
        return $value !== '' ? $value : null;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function boolFromPayload(array $payload, string $key): bool
    {
        return filter_var($payload[$key] ?? false, FILTER_VALIDATE_BOOL) === true;
    }

    private function safeError(string $error): string
    {
        $value = trim($error);
        if ($value === '') {
            return 'Sync failed.';
        }

        if (strlen($value) > 1000) {
            return substr($value, 0, 1000);
        }

        return $value;
    }
}

