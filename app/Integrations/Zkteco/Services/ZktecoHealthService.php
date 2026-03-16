<?php

namespace App\Integrations\Zkteco\Services;

use App\Integrations\Zkteco\Repositories\ZktecoConnectionRepository;
use App\Models\ZktecoDevice;
use App\Models\ZktecoMemberMap;

class ZktecoHealthService
{
    public function __construct(
        private readonly ZktecoConnectionRepository $connections,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function healthForBranch(?int $branch_id): array
    {
        if (!$branch_id) {
            return $this->emptyHealth();
        }

        $connection = $this->connections->forBranch($branch_id);

        if (!$connection) {
            return $this->emptyHealth();
        }

        $online_devices = ZktecoDevice::query()
            ->withoutBranchScope()
            ->where('zkteco_connection_id', $connection->id)
            ->where('is_online', true)
            ->count();

        $mapped_devices = $connection->branchMappings()
            ->withoutBranchScope()
            ->where('branch_id', $branch_id)
            ->where('is_active', true)
            ->count();

        $pending_biometrics = ZktecoMemberMap::query()
            ->withoutBranchScope()
            ->where('zkteco_connection_id', $connection->id)
            ->where('biometric_status', ZktecoMemberMap::BIOMETRIC_PENDING)
            ->count();

        return [
            'connected' => $connection->status === \App\Models\ZktecoConnection::STATUS_CONNECTED,
            'status' => $connection->status,
            'connection_id' => $connection->id,
            'last_tested_at' => $connection->last_tested_at,
            'last_test_success_at' => $connection->last_test_success_at,
            'last_personnel_sync_at' => $connection->last_personnel_sync_at,
            'last_event_sync_at' => $connection->last_event_sync_at,
            'online_devices_count' => $online_devices,
            'mapped_devices_count' => $mapped_devices,
            'pending_biometrics_count' => $pending_biometrics,
            'last_error' => $connection->last_error,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyHealth(): array
    {
        return [
            'connected' => false,
            'status' => 'not_configured',
            'connection_id' => null,
            'last_tested_at' => null,
            'last_test_success_at' => null,
            'last_personnel_sync_at' => null,
            'last_event_sync_at' => null,
            'online_devices_count' => 0,
            'mapped_devices_count' => 0,
            'pending_biometrics_count' => 0,
            'last_error' => null,
        ];
    }
}

