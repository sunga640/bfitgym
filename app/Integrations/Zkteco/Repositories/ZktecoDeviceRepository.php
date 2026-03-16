<?php

namespace App\Integrations\Zkteco\Repositories;

use App\Models\ZktecoBranchMapping;
use App\Models\ZktecoConnection;
use App\Models\ZktecoDevice;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ZktecoDeviceRepository
{
    /**
     * @param  array<int, array<string, mixed>>  $devices
     */
    public function storeDiscoveredDevices(ZktecoConnection $connection, array $devices): int
    {
        $stored = 0;

        foreach ($devices as $device) {
            $remote_device_id = (string) ($device['remote_device_id'] ?? '');
            if ($remote_device_id === '') {
                continue;
            }

            ZktecoDevice::query()
                ->withoutBranchScope()
                ->updateOrCreate(
                    [
                        'zkteco_connection_id' => $connection->id,
                        'remote_device_id' => $remote_device_id,
                    ],
                    [
                        'branch_id' => $connection->branch_id,
                        'remote_name' => $device['remote_name'] ?? $remote_device_id,
                        'remote_type' => $device['remote_type'] ?? null,
                        'remote_status' => $device['remote_status'] ?? null,
                        'is_online' => (bool) ($device['is_online'] ?? false),
                        'is_assignable' => (bool) ($device['is_assignable'] ?? true),
                        'last_seen_at' => $device['last_seen_at'] ?? null,
                        'remote_payload' => $device['remote_payload'] ?? null,
                    ]
                );

            $stored++;
        }

        return $stored;
    }

    /**
     * @param  array<int, int>  $device_ids
     */
    public function saveBranchMappings(int $branch_id, ZktecoConnection $connection, array $device_ids, ?int $actor_id = null): void
    {
        $valid_devices = ZktecoDevice::query()
            ->withoutBranchScope()
            ->where('zkteco_connection_id', $connection->id)
            ->where('is_assignable', true)
            ->whereIn('id', $device_ids)
            ->pluck('id')
            ->all();

        DB::transaction(function () use ($branch_id, $connection, $valid_devices, $actor_id) {
            ZktecoBranchMapping::query()
                ->withoutBranchScope()
                ->where('branch_id', $branch_id)
                ->where('zkteco_connection_id', $connection->id)
                ->update([
                    'is_active' => false,
                    'updated_by' => $actor_id,
                ]);

            foreach ($valid_devices as $device_id) {
                ZktecoBranchMapping::query()
                    ->withoutBranchScope()
                    ->updateOrCreate(
                        [
                            'branch_id' => $branch_id,
                            'zkteco_connection_id' => $connection->id,
                            'zkteco_device_id' => $device_id,
                        ],
                        [
                            'is_active' => true,
                            'created_by' => $actor_id,
                            'updated_by' => $actor_id,
                        ]
                    );
            }
        });
    }

    /**
     * @return Collection<int, ZktecoDevice>
     */
    public function mappedDevices(int $branch_id, int $connection_id): Collection
    {
        return ZktecoDevice::query()
            ->withoutBranchScope()
            ->whereHas('branchMappings', function ($query) use ($branch_id, $connection_id) {
                $query->withoutBranchScope()
                    ->where('branch_id', $branch_id)
                    ->where('zkteco_connection_id', $connection_id)
                    ->where('is_active', true);
            })
            ->orderBy('remote_name')
            ->get();
    }

    /**
     * @return array<int, int>
     */
    public function mappedDeviceIds(int $branch_id, int $connection_id): array
    {
        return ZktecoBranchMapping::query()
            ->withoutBranchScope()
            ->where('branch_id', $branch_id)
            ->where('zkteco_connection_id', $connection_id)
            ->where('is_active', true)
            ->pluck('zkteco_device_id')
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public function mappedRemoteDeviceIds(int $branch_id, int $connection_id): array
    {
        return ZktecoDevice::query()
            ->withoutBranchScope()
            ->whereIn('id', $this->mappedDeviceIds($branch_id, $connection_id))
            ->pluck('remote_device_id')
            ->filter()
            ->values()
            ->all();
    }
}
