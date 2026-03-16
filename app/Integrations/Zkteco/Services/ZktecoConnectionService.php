<?php

namespace App\Integrations\Zkteco\Services;

use App\Integrations\Zkteco\DTO\ConnectionTestResult;
use App\Integrations\Zkteco\Repositories\ZktecoConnectionRepository;
use App\Integrations\Zkteco\ZktecoProviderManager;
use App\Models\User;
use App\Models\ZktecoConnection;
use App\Models\ZktecoSyncRun;
use App\Support\AccessLogger;

class ZktecoConnectionService
{
    public function __construct(
        private readonly ZktecoConnectionRepository $connections,
        private readonly ZktecoProviderManager $providers,
        private readonly AccessLogger $logger,
    ) {
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function saveConnection(int $branch_id, array $payload, ?User $actor = null): ZktecoConnection
    {
        $existing = $this->connections->forBranch($branch_id);

        $attributes = [
            'provider' => ZktecoConnection::PROVIDER_ZKBIO_API,
            'base_url' => $this->normalizeBaseUrl((string) ($payload['base_url'] ?? '')),
            'port' => !empty($payload['port']) ? (int) $payload['port'] : null,
            'username' => !empty($payload['username']) ? trim((string) $payload['username']) : null,
            'ssl_enabled' => (bool) ($payload['ssl_enabled'] ?? true),
            'allow_self_signed' => (bool) ($payload['allow_self_signed'] ?? false),
            'timeout_seconds' => max(1, (int) ($payload['timeout_seconds'] ?? 10)),
            'disconnected_at' => null,
            'status' => $existing?->status ?? ZktecoConnection::STATUS_DISCONNECTED,
        ];

        $should_clear_password = (bool) ($payload['clear_password'] ?? false);
        $should_clear_api_key = (bool) ($payload['clear_api_key'] ?? false);

        if ($should_clear_password) {
            $attributes['password'] = null;
        } elseif (!empty($payload['password'])) {
            $attributes['password'] = (string) $payload['password'];
        } elseif ($existing) {
            $attributes['password'] = $existing->password;
        }

        if ($should_clear_api_key) {
            $attributes['api_key'] = null;
        } elseif (!empty($payload['api_key'])) {
            $attributes['api_key'] = (string) $payload['api_key'];
        } elseif ($existing) {
            $attributes['api_key'] = $existing->api_key;
        }

        $connection = $this->connections->saveForBranch($branch_id, $attributes);

        $this->logger->info('zkteco_connection_saved', [
            'branch_id' => $branch_id,
            'connection_id' => $connection->id,
            'actor_id' => $actor?->id,
            'provider' => $connection->provider,
        ]);

        return $connection;
    }

    public function testConnection(ZktecoConnection $connection, ?User $actor = null): ConnectionTestResult
    {
        $run = $this->connections->startSyncRun(
            connection: $connection,
            run_type: ZktecoSyncRun::TYPE_CONNECTION_TEST,
            triggered_by_user_id: $actor?->id,
        );

        $provider = $this->providers->resolve($connection);
        $result = $provider->testConnection($connection);
        $message = $result->message;

        if (!$result->ok && $result->status === ZktecoConnection::STATUS_UNREACHABLE && $connection->hasPrivateHost()) {
            $message = 'Host unreachable. This looks like a private LAN IP. If FitHub is cloud-hosted, expose ZKBio through VPN/reverse proxy or run FitHub inside the same network.';
        }

        $connection->update([
            'status' => $result->status,
            'last_tested_at' => now(),
            'last_test_success_at' => $result->ok ? now() : $connection->last_test_success_at,
            'last_error' => $result->ok ? null : $message,
            'metadata' => array_merge($connection->metadata ?? [], [
                'last_test_details' => $result->details,
            ]),
        ]);

        $this->connections->finishSyncRun(
            run: $run,
            status: $result->ok ? ZktecoSyncRun::STATUS_SUCCESS : ZktecoSyncRun::STATUS_FAILED,
            records_total: 1,
            records_success: $result->ok ? 1 : 0,
            records_failed: $result->ok ? 0 : 1,
            error_message: $result->ok ? null : $message
        );

        if ($result->ok) {
            $this->logger->info('zkteco_connection_test_passed', [
                'branch_id' => $connection->branch_id,
                'connection_id' => $connection->id,
                'actor_id' => $actor?->id,
            ]);
        } else {
            $this->logger->error('zkteco_connection_test_failed', [
                'branch_id' => $connection->branch_id,
                'connection_id' => $connection->id,
                'actor_id' => $actor?->id,
                'status' => $result->status,
                'message' => $message,
            ]);
        }

        return new ConnectionTestResult(
            ok: $result->ok,
            status: $result->status,
            message: $message,
            details: $result->details,
        );
    }

    public function disconnect(ZktecoConnection $connection, ?User $actor = null): void
    {
        $connection->update([
            'status' => ZktecoConnection::STATUS_DISCONNECTED,
            'disconnected_at' => now(),
            'last_error' => null,
        ]);

        $this->logger->info('zkteco_connection_disconnected', [
            'branch_id' => $connection->branch_id,
            'connection_id' => $connection->id,
            'actor_id' => $actor?->id,
        ]);
    }

    private function normalizeBaseUrl(string $base_url): string
    {
        return rtrim(trim($base_url), '/');
    }
}

