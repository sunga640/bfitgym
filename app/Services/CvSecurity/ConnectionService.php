<?php

namespace App\Services\CvSecurity;

use App\Models\CvSecurityConnection;
use App\Models\CvSecuritySyncState;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ConnectionService
{
    public function __construct(
        private readonly ActivityLogger $activity_logger,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function save(array $data, User $actor, ?CvSecurityConnection $connection = null): CvSecurityConnection
    {
        return DB::transaction(function () use ($data, $actor, $connection) {
            $payload = [
                'branch_id' => (int) $data['branch_id'],
                'name' => trim((string) $data['name']),
                'agent_label' => $this->nullableString($data, 'agent_label'),
                'cv_base_url' => $this->nullableString($data, 'cv_base_url'),
                'cv_port' => isset($data['cv_port']) && $data['cv_port'] !== '' ? (int) $data['cv_port'] : null,
                'cv_username' => $this->nullableString($data, 'cv_username'),
                'poll_interval_seconds' => max(5, (int) ($data['poll_interval_seconds'] ?? 30)),
                'timezone' => $this->nullableString($data, 'timezone') ?: config('app.timezone'),
                'notes' => $this->nullableString($data, 'notes'),
                'updated_by' => $actor->id,
            ];

            if (!empty($data['cv_password'])) {
                $payload['cv_password'] = (string) $data['cv_password'];
            } elseif (($data['clear_cv_password'] ?? false) === true) {
                $payload['cv_password'] = null;
            }

            if (!empty($data['cv_api_token'])) {
                $payload['cv_api_token'] = (string) $data['cv_api_token'];
            } elseif (($data['clear_cv_api_token'] ?? false) === true) {
                $payload['cv_api_token'] = null;
            }

            if ($connection && $connection->exists) {
                $connection->update($payload);
                $saved = $connection->fresh();
                $event = 'connection_updated';
                $message = 'CVSecurity connection updated.';
            } else {
                $saved = CvSecurityConnection::query()->create([
                    ...$payload,
                    'status' => CvSecurityConnection::STATUS_PENDING,
                    'pairing_status' => CvSecurityConnection::PAIRING_UNPAIRED,
                    'agent_status' => 'offline',
                    'cvsecurity_status' => 'unknown',
                    'created_by' => $actor->id,
                ]);

                $event = 'connection_created';
                $message = 'CVSecurity connection created.';
            }

            CvSecuritySyncState::query()->firstOrCreate(
                ['cvsecurity_connection_id' => $saved->id],
                ['branch_id' => $saved->branch_id]
            );

            $this->activity_logger->log(
                connection: $saved,
                level: 'info',
                event: $event,
                message: $message,
                context: [
                    'actor_id' => $actor->id,
                ],
            );

            return $saved;
        });
    }

    public function requestAgentTest(CvSecurityConnection $connection, User $actor): void
    {
        $connection->update([
            'agent_test_requested' => true,
            'updated_by' => $actor->id,
        ]);

        $this->activity_logger->log(
            connection: $connection,
            level: 'info',
            event: 'agent_test_requested',
            message: 'Manual CVSecurity connection test requested by admin.',
            context: ['actor_id' => $actor->id],
        );
    }

    public function requestAgentEventPull(CvSecurityConnection $connection, User $actor): void
    {
        $connection->update([
            'agent_event_pull_requested' => true,
            'updated_by' => $actor->id,
        ]);

        $this->activity_logger->log(
            connection: $connection,
            level: 'info',
            event: 'agent_event_pull_requested',
            message: 'Manual event pull requested by admin.',
            context: ['actor_id' => $actor->id],
        );
    }

    public function markConnected(CvSecurityConnection $connection): void
    {
        $connection->update([
            'status' => CvSecurityConnection::STATUS_CONNECTED,
            'agent_status' => 'online',
            'last_heartbeat_at' => now(),
        ]);
    }

    public function disconnect(CvSecurityConnection $connection, User $actor): void
    {
        DB::transaction(function () use ($connection, $actor) {
            $connection->agents()->update([
                'status' => 'revoked',
                'last_error' => 'Disconnected by admin.',
                'last_error_at' => now(),
            ]);

            $connection->update([
                'status' => CvSecurityConnection::STATUS_DISCONNECTED,
                'pairing_status' => CvSecurityConnection::PAIRING_UNPAIRED,
                'agent_status' => 'offline',
                'cvsecurity_status' => 'unknown',
                'agent_test_requested' => false,
                'agent_sync_requested' => false,
                'agent_event_pull_requested' => false,
                'updated_by' => $actor->id,
            ]);

            $this->activity_logger->log(
                connection: $connection,
                level: 'warning',
                event: 'connection_disconnected',
                message: 'Integration disconnected by admin.',
                context: ['actor_id' => $actor->id],
            );
        });
    }

    public function disable(CvSecurityConnection $connection, User $actor): void
    {
        $connection->update([
            'status' => CvSecurityConnection::STATUS_DISABLED,
            'disabled_at' => now(),
            'updated_by' => $actor->id,
        ]);

        $this->activity_logger->log(
            connection: $connection,
            level: 'warning',
            event: 'connection_disabled',
            message: 'Integration disabled by admin.',
            context: ['actor_id' => $actor->id],
        );
    }

    private function nullableString(array $data, string $key): ?string
    {
        if (!array_key_exists($key, $data)) {
            return null;
        }

        $value = trim((string) $data[$key]);
        return $value !== '' ? $value : null;
    }
}
