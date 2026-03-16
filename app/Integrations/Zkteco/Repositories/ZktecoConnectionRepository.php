<?php

namespace App\Integrations\Zkteco\Repositories;

use App\Models\ZktecoConnection;
use App\Models\ZktecoSyncRun;

class ZktecoConnectionRepository
{
    public function forBranch(int $branch_id): ?ZktecoConnection
    {
        return ZktecoConnection::query()
            ->withoutBranchScope()
            ->where('branch_id', $branch_id)
            ->first();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function saveForBranch(int $branch_id, array $attributes): ZktecoConnection
    {
        return ZktecoConnection::query()
            ->withoutBranchScope()
            ->updateOrCreate(
                ['branch_id' => $branch_id],
                $attributes
            );
    }

    public function enabledConnections()
    {
        return ZktecoConnection::query()
            ->withoutBranchScope()
            ->whereNot('status', ZktecoConnection::STATUS_DISCONNECTED)
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function startSyncRun(
        ZktecoConnection $connection,
        string $run_type,
        ?int $triggered_by_user_id = null,
        array $context = []
    ): ZktecoSyncRun {
        return ZktecoSyncRun::query()
            ->withoutBranchScope()
            ->create([
                'branch_id' => $connection->branch_id,
                'zkteco_connection_id' => $connection->id,
                'run_type' => $run_type,
                'status' => ZktecoSyncRun::STATUS_RUNNING,
                'started_at' => now(),
                'context' => $context ?: null,
                'triggered_by_user_id' => $triggered_by_user_id,
            ]);
    }

    public function finishSyncRun(
        ZktecoSyncRun $run,
        string $status,
        int $records_total,
        int $records_success,
        int $records_failed,
        ?string $error_message = null
    ): void {
        $run->update([
            'status' => $status,
            'finished_at' => now(),
            'records_total' => $records_total,
            'records_success' => $records_success,
            'records_failed' => $records_failed,
            'error_message' => $error_message,
        ]);
    }
}

