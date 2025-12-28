<?php

namespace App\Console\Commands;

use App\Models\AccessControlDevice;
use App\Models\AccessControlDeviceCommand;
use App\Models\AccessIdentity;
use App\Models\Member;
use App\Services\AccessControl\AccessControlCommandService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class TestFingerprintEnrollCommand extends Command
{
    protected $signature = 'test:user-sync
        {member_id : The member ID to sync to device}
        {--device= : Specific device ID (defaults to first active device for member branch)}
        {--valid-days=365 : Number of days the access is valid}';

    protected $description = 'Queue a user sync (person_upsert) command for testing. Fingerprint is added manually via device web dashboard.';

    public function handle(): int
    {
        $member_id = (int) $this->argument('member_id');
        $valid_days = (int) ($this->option('valid-days') ?? 365);

        $member = Member::find($member_id);
        if (!$member) {
            $this->error("Member ID {$member_id} not found.");
            return self::FAILURE;
        }

        $this->info('============================================');
        $this->info('   USER SYNC TO DEVICE TEST');
        $this->info('============================================');
        $this->newLine();

        $member_name = trim("{$member->first_name} {$member->last_name}");
        $this->line("Member: {$member_name} (ID: {$member->id})");
        $this->line("Member No: {$member->member_no}");
        $this->line("Branch ID: {$member->branch_id}");
        $this->newLine();

        // Get or create device
        $device_id = $this->option('device');
        if ($device_id) {
            $device = AccessControlDevice::find((int) $device_id);
        } else {
            $device = AccessControlDevice::query()
                ->where('branch_id', $member->branch_id)
                ->where('status', 'active')
                ->first();
        }

        if (!$device) {
            $this->error('No active access control device found for this branch.');
            return self::FAILURE;
        }

        $this->line("Device: {$device->name} (ID: {$device->id})");
        $this->newLine();

        // Generate device_user_id
        $device_user_id = $member->member_no;
        $this->line("Device User ID: {$device_user_id}");

        // Check/create AccessIdentity
        $access_identity = AccessIdentity::query()
            ->where('branch_id', $member->branch_id)
            ->where('subject_type', AccessIdentity::SUBJECT_MEMBER)
            ->where('subject_id', $member->id)
            ->first();

        $valid_from = Carbon::now();
        $valid_until = Carbon::now()->addDays($valid_days);

        if (!$access_identity) {
            $this->info('Creating new AccessIdentity...');
            $access_identity = AccessIdentity::create([
                'branch_id' => $member->branch_id,
                'subject_type' => AccessIdentity::SUBJECT_MEMBER,
                'subject_id' => $member->id,
                'device_user_id' => $device_user_id,
                'is_active' => true, // Active immediately for user sync
                'device_synced_at' => null, // Will be set on command success
                'valid_from' => $valid_from,
                'valid_until' => $valid_until,
            ]);
        } else {
            $this->info('Using existing AccessIdentity: ' . $access_identity->id);
            // Reset it for testing
            $access_identity->update([
                'is_active' => true,
                'device_synced_at' => null,
                'valid_from' => $valid_from,
                'valid_until' => $valid_until,
                'last_sync_error' => null,
            ]);
        }

        $this->line("AccessIdentity ID: {$access_identity->id}");
        $this->newLine();

        // Create the command
        $command_id = (string) Str::orderedUuid();
        
        $payload = [
            'access_identity_id' => $access_identity->id,
            'device_user_id' => $device_user_id,
            'full_name' => $member_name,
            'subject_type' => AccessIdentity::SUBJECT_MEMBER,
            'subject_id' => $member->id,
            'valid_from' => $valid_from->toIso8601String(),
            'valid_to' => $valid_until->endOfDay()->toIso8601String(),
            'reason' => 'test_command',
        ];

        $command = AccessControlDeviceCommand::create([
            'id' => $command_id,
            'branch_id' => $member->branch_id,
            'access_control_device_id' => $device->id,
            'type' => AccessControlDeviceCommand::TYPE_PERSON_UPSERT,
            'payload' => $payload,
            'subject_type' => AccessIdentity::SUBJECT_MEMBER,
            'subject_id' => $member->id,
            'status' => AccessControlDeviceCommand::STATUS_PENDING,
            'priority' => 10,
            'attempts' => 0,
            'max_attempts' => 10,
            'available_at' => now(),
        ]);

        $this->info('============================================');
        $this->info('   COMMAND QUEUED SUCCESSFULLY');
        $this->info('============================================');
        $this->newLine();
        
        $this->line("Command ID: {$command->id}");
        $this->line("Type: {$command->type}");
        $this->line("Status: {$command->status}");
        $this->line("Device ID: {$device->id}");
        $this->newLine();

        $this->line('Payload:');
        $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $this->newLine();

        $this->info('The local agent will pick up this command on its next poll.');
        $this->info('Watch the agent logs for execution details.');
        $this->newLine();

        $this->warn('After the user is synced to the device, add fingerprint manually via:');
        $this->line("  Device web dashboard: http://<device-ip>");
        $this->newLine();

        $this->line('To monitor:');
        $this->line('  1. Check agent logs: local/storage/logs/agent.log');
        $this->line('  2. Check cloud access log: cloud/storage/logs/access.log');
        $this->line("  3. Check command status: SELECT * FROM access_control_device_commands WHERE id = '{$command_id}'");
        $this->newLine();

        return self::SUCCESS;
    }
}

