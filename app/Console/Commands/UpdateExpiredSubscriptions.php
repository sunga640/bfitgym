<?php

namespace App\Console\Commands;

use App\Models\MemberSubscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdateExpiredSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'subscriptions:update-expired';

    /**
     * The console command description.
     */
    protected $description = 'Update subscriptions that have passed their end date to expired status';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Checking for expired subscriptions...');

        // Find all active subscriptions where end_date has passed
        $expired_subscriptions = MemberSubscription::withoutGlobalScopes()
            ->where('status', 'active')
            ->where('end_date', '<', now()->startOfDay())
            ->get();

        $count = $expired_subscriptions->count();

        if ($count === 0) {
            $this->info('No expired subscriptions found.');
            return Command::SUCCESS;
        }

        $this->info("Found {$count} expired subscription(s). Updating...");

        $updated = 0;
        $errors = 0;

        foreach ($expired_subscriptions as $subscription) {
            try {
                $subscription->update(['status' => 'expired']);

                Log::info('Subscription automatically expired', [
                    'subscription_id' => $subscription->id,
                    'member_id' => $subscription->member_id,
                    'branch_id' => $subscription->branch_id,
                    'end_date' => $subscription->end_date->toDateString(),
                ]);

                $updated++;
            } catch (\Exception $e) {
                Log::error('Failed to expire subscription', [
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage(),
                ]);

                $this->error("Failed to expire subscription ID: {$subscription->id}");
                $errors++;
            }
        }

        $this->info("Updated {$updated} subscription(s) to expired status.");

        if ($errors > 0) {
            $this->warn("{$errors} subscription(s) failed to update. Check logs for details.");
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}

