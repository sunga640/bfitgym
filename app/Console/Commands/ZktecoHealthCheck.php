<?php

namespace App\Console\Commands;

use App\Integrations\Zkteco\Repositories\ZktecoConnectionRepository;
use App\Integrations\Zkteco\Services\ZktecoConnectionService;
use Illuminate\Console\Command;

class ZktecoHealthCheck extends Command
{
    protected $signature = 'zkteco:health-check {--branch_id=}';

    protected $description = 'Run ZKTeco/ZKBio connectivity health checks for configured branches.';

    public function handle(
        ZktecoConnectionRepository $connections,
        ZktecoConnectionService $service
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
            $result = $service->testConnection($connection);
            $status = $result->ok ? 'OK' : 'FAIL';
            $this->line("Branch {$connection->branch_id} [{$status}] {$result->message}");
        }

        return self::SUCCESS;
    }
}

