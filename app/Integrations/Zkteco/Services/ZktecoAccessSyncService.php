<?php

namespace App\Integrations\Zkteco\Services;

use App\Integrations\Zkteco\ZktecoProviderManager;
use App\Models\ZktecoConnection;
use App\Models\ZktecoDevice;
use App\Models\ZktecoMemberDeviceAccess;
use App\Models\ZktecoMemberMap;
use Illuminate\Support\Carbon;

class ZktecoAccessSyncService
{
    public function __construct(
        private readonly ZktecoProviderManager $providers,
    ) {
    }

    /**
     * @param  array<int, string>  $remote_device_ids
     * @return array<string, mixed>
     */
    public function syncMemberAccess(
        ZktecoConnection $connection,
        ZktecoMemberMap $member_map,
        bool $grant,
        array $remote_device_ids,
        ?Carbon $valid_until = null
    ): array {
        $provider = $this->providers->resolve($connection);

        $payload = [
            'personnel_id' => $member_map->remote_personnel_id,
            'personnel_code' => $member_map->remote_personnel_code,
            'grant' => $grant,
            'device_ids' => array_values($remote_device_ids),
            'valid_until' => $grant && $valid_until ? $valid_until->toIso8601String() : null,
        ];

        $response = $provider->syncAccess($connection, $payload);

        if ($grant) {
            $device_ids = ZktecoDevice::query()
                ->withoutBranchScope()
                ->where('zkteco_connection_id', $connection->id)
                ->whereIn('remote_device_id', $remote_device_ids)
                ->pluck('id')
                ->all();

            foreach ($device_ids as $device_id) {
                ZktecoMemberDeviceAccess::query()
                    ->updateOrCreate(
                        [
                            'zkteco_member_map_id' => $member_map->id,
                            'zkteco_device_id' => $device_id,
                        ],
                        [
                            'access_granted' => true,
                            'starts_at' => now(),
                            'ends_at' => $valid_until,
                        ]
                    );
            }
        } else {
            ZktecoMemberDeviceAccess::query()
                ->where('zkteco_member_map_id', $member_map->id)
                ->update([
                    'access_granted' => false,
                    'ends_at' => now(),
                ]);
        }

        return $response;
    }
}

