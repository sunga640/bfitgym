<?php

namespace App\Console\Commands;

use App\Models\CvSecurityAgent;
use App\Models\CvSecurityConnection;
use Illuminate\Console\Command;

class CvSecurityMarkAgentsOfflineCommand extends Command
{
    protected $signature = 'cvsecurity:mark-agents-offline {--minutes=3 : Offline threshold in minutes}';

    protected $description = 'Mark stale CVSecurity agents as offline/error-safe for dashboard status.';

    public function handle(): int
    {
        $threshold = max(1, (int) $this->option('minutes'));
        $cutoff = now()->subMinutes($threshold);

        $stale_agents = CvSecurityAgent::query()
            ->withoutBranchScope()
            ->where('status', CvSecurityAgent::STATUS_ACTIVE)
            ->where(function ($q) use ($cutoff) {
                $q->whereNull('last_seen_at')
                    ->orWhere('last_seen_at', '<', $cutoff);
            })
            ->get();

        foreach ($stale_agents as $agent) {
            $agent->update(['status' => CvSecurityAgent::STATUS_OFFLINE]);
        }

        CvSecurityConnection::query()
            ->withoutBranchScope()
            ->whereHas('agents', function ($q) {
                $q->where('status', CvSecurityAgent::STATUS_ACTIVE);
            })
            ->update(['agent_status' => 'online']);

        CvSecurityConnection::query()
            ->withoutBranchScope()
            ->whereDoesntHave('agents', function ($q) {
                $q->where('status', CvSecurityAgent::STATUS_ACTIVE);
            })
            ->update(['agent_status' => 'offline']);

        $this->info('Marked ' . $stale_agents->count() . ' agent(s) offline.');

        return self::SUCCESS;
    }
}

