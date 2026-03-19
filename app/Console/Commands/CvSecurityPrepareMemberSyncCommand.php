<?php

namespace App\Console\Commands;

use App\Models\CvSecurityConnection;
use App\Services\CvSecurity\MemberSyncPlanner;
use Illuminate\Console\Command;

class CvSecurityPrepareMemberSyncCommand extends Command
{
    protected $signature = 'cvsecurity:prepare-member-sync
        {--connection_id= : Limit to a specific cvsecurity connection ID}
        {--branch_id= : Limit to a specific branch ID}';

    protected $description = 'Prepare outbound member access sync items for CVSecurity local agents.';

    public function handle(MemberSyncPlanner $planner): int
    {
        $query = CvSecurityConnection::query()
            ->withoutBranchScope()
            ->where('status', '!=', CvSecurityConnection::STATUS_DISABLED);

        if ($this->option('connection_id')) {
            $query->where('id', (int) $this->option('connection_id'));
        }

        if ($this->option('branch_id')) {
            $query->where('branch_id', (int) $this->option('branch_id'));
        }

        $connections = $query->orderBy('id')->get();
        if ($connections->isEmpty()) {
            $this->line('No eligible CVSecurity connections found.');
            return self::SUCCESS;
        }

        $total_created = 0;
        $total_skipped = 0;

        foreach ($connections as $connection) {
            $result = $planner->planForConnection($connection);
            $total_created += $result['created'];
            $total_skipped += $result['skipped'];

            $this->line(sprintf(
                'Connection #%d (%s): created=%d, skipped=%d, members=%d',
                $connection->id,
                $connection->name,
                $result['created'],
                $result['skipped'],
                $result['total'],
            ));
        }

        $this->info(sprintf('Done. created=%d skipped=%d', $total_created, $total_skipped));

        return self::SUCCESS;
    }
}

