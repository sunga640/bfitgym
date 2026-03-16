<?php

namespace App\Integrations\Zkteco\Services;

use App\Integrations\Zkteco\Repositories\ZktecoConnectionRepository;
use App\Integrations\Zkteco\Repositories\ZktecoDeviceRepository;
use App\Integrations\Zkteco\ZktecoProviderManager;
use App\Models\User;
use App\Models\ZktecoConnection;
use App\Models\ZktecoSyncRun;
use App\Support\AccessLogger;

class ZktecoDeviceDiscoveryService
{
    public function __construct(
        private readonly ZktecoProviderManager $providers,
        private readonly ZktecoDeviceRepository $devices,
        private readonly ZktecoConnectionRepository $connections,
        private readonly AccessLogger $logger,
    ) {
    }

    /**
     * @return array{stored:int, discovered:int}
     */
    public function fetchAndStore(ZktecoConnection $connection, ?User $actor = null): array
    {
        $run = $this->connections->startSyncRun(
            connection: $connection,
            run_type: ZktecoSyncRun::TYPE_DEVICE_FETCH,
            triggered_by_user_id: $actor?->id,
        );

        try {
            $provider = $this->providers->resolve($connection);
            $discovered = $provider->fetchDevices($connection);
            $stored = $this->devices->storeDiscoveredDevices($connection, $discovered);

            $connection->update([
                'status' => ZktecoConnection::STATUS_CONNECTED,
                'last_error' => null,
            ]);

            $this->connections->finishSyncRun(
                run: $run,
                status: ZktecoSyncRun::STATUS_SUCCESS,
                records_total: count($discovered),
                records_success: $stored,
                records_failed: max(0, count($discovered) - $stored),
            );

            $this->logger->info('zkteco_devices_fetched', [
                'branch_id' => $connection->branch_id,
                'connection_id' => $connection->id,
                'actor_id' => $actor?->id,
                'discovered' => count($discovered),
                'stored' => $stored,
            ]);

            return [
                'stored' => $stored,
                'discovered' => count($discovered),
            ];
        } catch (\Throwable $e) {
            $connection->update([
                'status' => ZktecoConnection::STATUS_ERROR,
                'last_error' => $e->getMessage(),
            ]);

            $this->connections->finishSyncRun(
                run: $run,
                status: ZktecoSyncRun::STATUS_FAILED,
                records_total: 0,
                records_success: 0,
                records_failed: 1,
                error_message: $e->getMessage()
            );

            $this->logger->error('zkteco_device_fetch_failed', [
                'branch_id' => $connection->branch_id,
                'connection_id' => $connection->id,
                'actor_id' => $actor?->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}

