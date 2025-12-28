<?php

namespace App\Console\Commands;

use App\Models\AccessIdentity;
use App\Models\Member;
use Illuminate\Console\Command;

class LinkDeviceUser extends Command
{
    protected $signature = 'device:link-user 
                            {device_user_id : The Employee No from the Hikvision device}
                            {member_id : The Member ID to link to}
                            {--branch= : Branch ID (defaults to member branch)}';

    protected $description = 'Link a Hikvision device user (Employee No) to a system member';

    public function handle(): int
    {
        $device_user_id = $this->argument('device_user_id');
        $member_id = $this->argument('member_id');

        // Find member
        $member = Member::find($member_id);
        if (!$member) {
            $this->error("Member with ID {$member_id} not found.");
            $this->newLine();
            $this->info('Available members:');
            Member::take(10)->get()->each(function ($m) {
                $this->line("  ID: {$m->id} - {$m->first_name} {$m->last_name}");
            });
            return self::FAILURE;
        }

        $branch_id = $this->option('branch') ?? $member->branch_id;

        // Check if identity already exists
        $existing = AccessIdentity::query()
            ->where('branch_id', $branch_id)
            ->where('device_user_id', $device_user_id)
            ->first();

        if ($existing) {
            $this->warn("An AccessIdentity already exists for device_user_id '{$device_user_id}' in branch {$branch_id}");
            $this->line("  Current link: {$existing->subject_type} ID {$existing->subject_id}");

            if (!$this->confirm('Do you want to update it?')) {
                return self::FAILURE;
            }

            $existing->update([
                'subject_type' => AccessIdentity::SUBJECT_MEMBER,
                'subject_id' => $member->id,
                'status' => 'active',
            ]);

            $this->info("✓ Updated AccessIdentity!");
        } else {
            AccessIdentity::create([
                'branch_id' => $branch_id,
                'device_user_id' => $device_user_id,
                'subject_type' => AccessIdentity::SUBJECT_MEMBER,
                'subject_id' => $member->id,
                'status' => 'active',
            ]);

            $this->info("✓ Created AccessIdentity!");
        }

        $this->newLine();
        $this->info("Linked device user '{$device_user_id}' to member:");
        $this->line("  Name: {$member->first_name} {$member->last_name}");
        $this->line("  Member ID: {$member->id}");
        $this->line("  Branch: {$branch_id}");

        // Check if member has active subscription
        $has_subscription = $member->subscriptions()
            ->where('status', 'active')
            ->whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now())
            ->exists();

        $has_insurance = $member->insurances()
            ->where('status', 'active')
            ->whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now())
            ->exists();

        $this->newLine();
        if ($has_subscription || $has_insurance) {
            $this->info("✓ Member has active subscription/insurance - access logs will be recorded!");
        } else {
            $this->warn("⚠ Member has NO active subscription or insurance!");
            $this->warn("  Access events will be skipped until the member has an active subscription.");
        }

        return self::SUCCESS;
    }
}
