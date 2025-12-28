<?php

namespace App\Console\Commands;

use App\Services\AccessControl\AccessControlService;
use Illuminate\Console\Command;

class DisableExpiredAccess extends Command
{
    protected $signature = 'access:disable-expired';

    protected $description = 'Disable fingerprint access for members with expired subscriptions or insurance';

    public function handle(): int
    {
        $this->info('Checking for expired member access...');

        $service = app(AccessControlService::class);
        $result = $service->disableExpiredMemberAccess();

        $this->info("Disabled {$result['disabled_count']} member(s) with expired subscriptions/insurance.");

        if (!empty($result['errors'])) {
            $this->warn('Some errors occurred:');
            foreach ($result['errors'] as $error) {
                $this->line("  - {$error}");
            }
        }

        if ($result['disabled_count'] > 0) {
            $this->info('Disable commands have been enqueued for the local agent.');
        }

        return self::SUCCESS;
    }
}

