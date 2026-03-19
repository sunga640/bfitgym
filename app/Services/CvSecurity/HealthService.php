<?php

namespace App\Services\CvSecurity;

use App\Models\CvSecurityConnection;

class HealthService
{
    /**
     * @return array<string, mixed>|null
     */
    public function healthForBranch(?int $branch_id): ?array
    {
        if (!$branch_id) {
            return null;
        }

        $connection = CvSecurityConnection::query()
            ->withoutBranchScope()
            ->where('branch_id', $branch_id)
            ->latest('id')
            ->first();

        if (!$connection) {
            return null;
        }

        $online_agents = $connection->agents()
            ->where('status', 'active')
            ->where('last_seen_at', '>=', now()->subMinutes(3))
            ->count();

        return [
            'status' => $connection->status,
            'online_devices_count' => $online_agents,
            'last_event_sync_at' => $connection->last_event_at,
            'last_error' => $connection->last_error,
        ];
    }
}
