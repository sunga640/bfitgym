<?php

namespace App\Integrations\Zkteco\Repositories;

use App\Models\ZktecoAccessEvent;
use App\Models\ZktecoConnection;

class ZktecoEventRepository
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(ZktecoConnection $connection, array $attributes): ZktecoAccessEvent
    {
        return ZktecoAccessEvent::query()
            ->withoutBranchScope()
            ->create([
                'branch_id' => $connection->branch_id,
                'zkteco_connection_id' => $connection->id,
                ...$attributes,
            ]);
    }

    public function exists(ZktecoConnection $connection, ?string $remote_event_id, string $event_fingerprint): bool
    {
        $query = ZktecoAccessEvent::query()
            ->withoutBranchScope()
            ->where('zkteco_connection_id', $connection->id);

        if (!empty($remote_event_id)) {
            return (clone $query)->where('remote_event_id', $remote_event_id)->exists();
        }

        return (clone $query)->where('event_fingerprint', $event_fingerprint)->exists();
    }
}

