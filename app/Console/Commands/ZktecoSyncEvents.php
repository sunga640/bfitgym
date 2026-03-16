<?php

namespace App\Console\Commands;

use App\Integrations\Zkteco\Repositories\ZktecoConnectionRepository;
use App\Integrations\Zkteco\Services\ZktecoEventImportService;
use Illuminate\Console\Command;

class ZktecoSyncEvents extends Command
{
    protected $signature = 'zkteco:sync-events {--branch_id=}';

    protected $description = 'Pull entry/exit events from ZKBio into FitHub for configured branches.';

    public function handle(
        ZktecoConnectionRepository $connections,
        ZktecoEventImportService $import_service
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
            $result = $import_service->syncBranch($connection);

            $this->line(
                "Branch {$connection->branch_id}: total={$result['total']} imported={$result['imported']} skipped={$result['skipped']} failed={$result['failed']}"
            );
        }

        return self::SUCCESS;
    }
}

