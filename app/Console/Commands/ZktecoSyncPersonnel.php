<?php

namespace App\Console\Commands;

use App\Integrations\Zkteco\Repositories\ZktecoConnectionRepository;
use App\Integrations\Zkteco\Services\ZktecoMemberSyncService;
use Illuminate\Console\Command;

class ZktecoSyncPersonnel extends Command
{
    protected $signature = 'zkteco:sync-personnel {--branch_id=}';

    protected $description = 'Sync FitHub members and access rights to ZKBio for configured branches.';

    public function handle(
        ZktecoConnectionRepository $connections,
        ZktecoMemberSyncService $sync_service
    ): int {
        $branch_id = $this->option('branch_id');
        $collection = $connections->enabledConnections();

        if (!empty($branch_id)) {
            $collection = $collection->where('branch_id', (int) $branch_id)->values();
        }

        if ($collection->isEmpty()) {
            $this->info('No enabled ZKTeco connections found.');
            return self::SUCCESS;
        }

        foreach ($collection as $connection) {
            $result = $sync_service->syncBranch($connection);

            $this->line(
                "Branch {$connection->branch_id}: total={$result['total']} success={$result['success']} failed={$result['failed']}"
            );
        }

        return self::SUCCESS;
    }
}

