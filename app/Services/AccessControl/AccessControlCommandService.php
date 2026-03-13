<?php

namespace App\Services\AccessControl;

use App\Exceptions\AccessControl\AccessControlActionException;
use App\Models\AccessControlDevice;
use App\Models\AccessControlDeviceCommand;
use App\Models\AccessIdentity;
use App\Models\Member;
use App\Models\User;
use App\Support\AccessLogger;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AccessControlCommandService
{
    public function enqueueDisableFingerprintForMember(Member $member, User $actor): array
    {
        $device_user_id = trim((string) ($member->member_no ?? ''));
        if ($device_user_id === '') {
            throw new AccessControlActionException('Member is missing member number; cannot build device_user_id.');
        }

        // Use last week for both valid_from and valid_to to ensure user is clearly expired
        // regardless of timezone differences between cloud and local agent.
        // Setting both dates to the same past date ensures:
        // 1. No timezone edge cases (a full week buffer)
        // 2. Device interprets this as expired user (not "long term effective")
        // 3. User record stays intact on device, just access period is invalid
        $last_week = Carbon::now()->subWeek()->startOfDay();
        $valid_from = $last_week->format('Y-m-d H:i:s');
        $valid_to = $last_week->copy()->endOfDay()->format('Y-m-d H:i:s');

        $eligibility = app(AccessEligibilityService::class);
        $allowed = $eligibility->isAllowed($member);
        $allowed_until = $eligibility->allowedUntil($member);

        $access_logger = app(AccessLogger::class);
        $access_logger->info('fingerprint_disable_requested', [
            'branch_id' => $member->branch_id,
            'member_id' => $member->id,
            'member_no' => $member->member_no,
            'device_user_id' => $device_user_id,
            'actor_user_id' => $actor->id,
            'valid_from' => $valid_from,
            'valid_to' => $valid_to,
            'eligibility_allowed' => $allowed,
            'eligibility_allowed_until' => $allowed_until?->toDateString(),
        ]);

        return $this->enqueueAccessSetValidityForMember($member, $valid_from, $valid_to, [
            'member_no' => $member->member_no,
            'device_user_id' => $device_user_id,
            'actor_user_id' => $actor->id,
            'valid_from' => $valid_from,
            'eligibility_allowed' => $allowed,
            'eligibility_allowed_until' => $allowed_until?->toDateString(),
        ]);
    }

    public function enqueueEnableFingerprintForMember(Member $member, User $actor): array
    {
        $device_user_id = trim((string) ($member->member_no ?? ''));
        if ($device_user_id === '') {
            throw new AccessControlActionException('Member is missing member number; cannot build device_user_id.');
        }

        $eligibility = app(AccessEligibilityService::class);

        if (! $eligibility->isAllowed($member)) {
            throw new AccessControlActionException('Member is not eligible (no active subscription/insurance).');
        }

        $allowed_until = $eligibility->allowedUntil($member);

        $valid_from = Carbon::now()->subMinutes(1)->format('Y-m-d H:i:s');
        $valid_to = $allowed_until === null ? null : $allowed_until->copy()->endOfDay()->format('Y-m-d H:i:s');

        $access_logger = app(AccessLogger::class);
        $access_logger->info('fingerprint_enable_requested', [
            'branch_id' => $member->branch_id,
            'member_id' => $member->id,
            'member_no' => $member->member_no,
            'device_user_id' => $device_user_id,
            'actor_user_id' => $actor->id,
            'valid_from' => $valid_from,
            'valid_to' => $valid_to,
            'eligibility_allowed' => true,
            'eligibility_allowed_until' => $allowed_until?->toDateString(),
        ]);

        return $this->enqueueAccessSetValidityForMember($member, $valid_from, $valid_to, [
            'member_no' => $member->member_no,
            'device_user_id' => $device_user_id,
            'actor_user_id' => $actor->id,
            'valid_from' => $valid_from,
            'eligibility_allowed' => true,
            'eligibility_allowed_until' => $allowed_until?->toDateString(),
        ]);
    }

    /**
     * Ensure a member has an AccessIdentity.
     *
     * - Find by: branch_id + subject_type='member' + subject_id
     * - Create if missing with:
     *   - device_user_id = member.member_no
     *   - is_active = true
     */
    public function ensure_access_identity_for_member(Member $member): AccessIdentity
    {
        if (empty($member->member_no)) {
            throw new \InvalidArgumentException('Member member_no is required to build device_user_id.');
        }

        $identity = AccessIdentity::query()
            ->withoutBranchScope()
            ->where('branch_id', $member->branch_id)
            ->where('integration_type', AccessControlDevice::INTEGRATION_HIKVISION)
            ->where('subject_type', AccessIdentity::SUBJECT_MEMBER)
            ->where('subject_id', $member->id)
            ->first();

        if ($identity) {
            return $identity;
        }

        return AccessIdentity::create([
            'branch_id' => $member->branch_id,
            'integration_type' => AccessControlDevice::INTEGRATION_HIKVISION,
            'provider' => AccessControlDevice::PROVIDER_HIKVISION_AGENT,
            'subject_type' => AccessIdentity::SUBJECT_MEMBER,
            'subject_id' => $member->id,
            'device_user_id' => $member->member_no,
            'is_active' => true,
        ]);
    }

    /**
     * Convert current eligibility state into device commands (outbox).
     *
     * - Cloud does NOT call device APIs
     * - Commands are created in access_control_device_commands as UUIDs
     * - Prevent queue noise: cancel older pending commands for same subject/device/type
     */
    public function enqueue_member_sync(Member $member, string $reason = 'system'): void
    {
        $eligibility = App::make(AccessEligibilityService::class);
        $today = now()->startOfDay();

        $allowed = $eligibility->is_member_allowed($member, $today);
        $allowed_until = $eligibility->allowed_until($member, $today); // nullable; null means infinite

        $identity = $this->ensure_access_identity_for_member($member);

        $devices = AccessControlDevice::query()
            ->withoutBranchScope()
            ->where('branch_id', $member->branch_id)
            ->forIntegration(AccessControlDevice::INTEGRATION_HIKVISION)
            ->active()
            ->get();

        foreach ($devices as $device) {
            if ($allowed) {
                $this->enqueueDeviceCommand(
                    device: $device,
                    type: AccessControlDeviceCommand::TYPE_PERSON_UPSERT,
                    payload: [
                        'device_user_id' => $identity->device_user_id,
                        'full_name' => $member->full_name,
                        'subject_type' => AccessIdentity::SUBJECT_MEMBER,
                        'subject_id' => $member->id,
                        'reason' => $reason,
                    ],
                    subject_type: AccessIdentity::SUBJECT_MEMBER,
                    subject_id: $member->id,
                    priority: 10,
                    available_at: $today,
                );

                $this->enqueueDeviceCommand(
                    device: $device,
                    type: AccessControlDeviceCommand::TYPE_ACCESS_SET_VALIDITY,
                    payload: [
                        'device_user_id' => $identity->device_user_id,
                        'valid_from' => $today->toIso8601String(),
                        'valid_to' => $allowed_until?->copy()->endOfDay()->toIso8601String(),
                        'subject_type' => AccessIdentity::SUBJECT_MEMBER,
                        'subject_id' => $member->id,
                        'reason' => $reason,
                    ],
                    subject_type: AccessIdentity::SUBJECT_MEMBER,
                    subject_id: $member->id,
                    priority: 10,
                    available_at: $today,
                );

                continue;
            }

            // Not allowed -> disable (higher priority than upsert/validity)
            $this->enqueueDeviceCommand(
                device: $device,
                type: AccessControlDeviceCommand::TYPE_PERSON_DISABLE,
                payload: [
                    'device_user_id' => $identity->device_user_id,
                    'reason' => $reason,
                    'subject_type' => AccessIdentity::SUBJECT_MEMBER,
                    'subject_id' => $member->id,
                ],
                subject_type: AccessIdentity::SUBJECT_MEMBER,
                subject_id: $member->id,
                priority: 20,
                available_at: $today,
            );
        }
    }

    /**
     * Enqueue a command for a specific device.
     *
     * IMPORTANT: Cloud must NEVER talk to devices directly.
     */
    public function enqueueDeviceCommand(
        AccessControlDevice $device,
        string $type,
        array $payload = [],
        ?string $subject_type = null,
        ?int $subject_id = null,
        int $priority = 0,
        ?Carbon $available_at = null,
    ): AccessControlDeviceCommand {
        return DB::transaction(function () use ($device, $type, $payload, $subject_type, $subject_id, $priority, $available_at) {
            // Cancel older pending commands for same device/subject/type to avoid queue noise.
            AccessControlDeviceCommand::query()
                ->where('branch_id', $device->branch_id)
                ->where('access_control_device_id', $device->id)
                ->where('type', $type)
                ->when($subject_type, fn($q) => $q->where('subject_type', $subject_type))
                ->when($subject_id, fn($q) => $q->where('subject_id', $subject_id))
                ->where('status', AccessControlDeviceCommand::STATUS_PENDING)
                ->update([
                    'status' => AccessControlDeviceCommand::STATUS_CANCELLED,
                    'finished_at' => now(),
                    'updated_at' => now(),
                ]);

            $command = AccessControlDeviceCommand::create([
                'branch_id' => $device->branch_id,
                'integration_type' => $device->integration_type,
                'provider' => $device->provider,
                'access_control_device_id' => $device->id,
                'subject_type' => $subject_type,
                'subject_id' => $subject_id,
                'type' => $type,
                'priority' => $priority,
                'status' => AccessControlDeviceCommand::STATUS_PENDING,
                'attempts' => 0,
                'max_attempts' => 10,
                'available_at' => $available_at ?? now(),
                'payload' => $payload,
            ]);

            Log::info('Access control command enqueued', [
                'command_id' => $command->id,
                'branch_id' => $command->branch_id,
                'device_id' => $command->access_control_device_id,
                'type' => $command->type,
                'subject_type' => $command->subject_type,
                'subject_id' => $command->subject_id,
            ]);

            return $command;
        });
    }

    /**
     * Enqueue the same command for all ACTIVE devices in a branch.
     */
    public function enqueueForBranchDevices(
        int $branch_id,
        string $type,
        array $payload = [],
        ?string $subject_type = null,
        ?int $subject_id = null,
        int $priority = 0,
        ?Carbon $available_at = null,
        string $integration_type = AccessControlDevice::INTEGRATION_HIKVISION,
        ?string $provider = null,
    ): int {
        $devices = AccessControlDevice::query()
            ->withoutBranchScope()
            ->where('branch_id', $branch_id)
            ->forIntegration($integration_type)
            ->when($provider, fn($q) => $q->forProvider($provider))
            ->active()
            ->get();

        $count = 0;

        foreach ($devices as $device) {
            $this->enqueueDeviceCommand(
                device: $device,
                type: $type,
                payload: $payload,
                subject_type: $subject_type,
                subject_id: $subject_id,
                priority: $priority,
                available_at: $available_at,
            );
            $count++;
        }

        return $count;
    }

    public function enqueuePersonUpsertForMember(Member $member, AccessIdentity $identity, array $validity): int
    {
        $payload = [
            'device_user_id' => $identity->device_user_id,
            'subject_type' => AccessIdentity::SUBJECT_MEMBER,
            'subject_id' => $member->id,
            'full_name' => $member->full_name,
            'valid_from' => $validity['start_date']?->toIso8601String(),
            'valid_to' => $validity['end_date']?->copy()->endOfDay()->toIso8601String(),
        ];

        return $this->enqueueForBranchDevices(
            branch_id: $member->branch_id,
            type: AccessControlDeviceCommand::TYPE_PERSON_UPSERT,
            payload: $payload,
            subject_type: AccessIdentity::SUBJECT_MEMBER,
            subject_id: $member->id,
            priority: 10,
        );
    }

    /**
     * Explicit admin action: enqueue access_set_validity for a member across active devices.
     *
     * @return array<int, string> created command UUIDs
     */
    public function enqueueAccessSetValidityForMember(Member $member, ?string $valid_from, ?string $valid_to, array $log_context = []): array
    {
        $device_user_id = trim((string) ($member->member_no ?? ''));
        if ($device_user_id === '') {
            throw new \InvalidArgumentException('Member member_no is required to build device_user_id.');
        }

        $devices = AccessControlDevice::query()
            ->withoutBranchScope()
            ->where('branch_id', $member->branch_id)
            ->forIntegration(AccessControlDevice::INTEGRATION_HIKVISION)
            ->active()
            ->get();

        $payload = [
            'device_user_id' => $device_user_id,
            'valid_from' => $valid_from,
            'valid_to' => $valid_to,
        ];

        $access_logger = app(AccessLogger::class);

        $command_uuids = [];

        foreach ($devices as $device) {
            $command = $this->enqueueDeviceCommand(
                device: $device,
                type: AccessControlDeviceCommand::TYPE_ACCESS_SET_VALIDITY,
                payload: $payload,
                subject_type: AccessIdentity::SUBJECT_MEMBER,
                subject_id: $member->id,
                priority: 20,
                available_at: now(),
            );

            $command_uuids[] = $command->id;

            $access_logger->info('command_enqueued', [
                'command_uuid' => $command->id,
                'device_id' => $device->id,
                'branch_id' => $device->branch_id,
                'member_id' => $member->id,
                'valid_to' => $valid_to,
                ...$log_context,
            ]);
        }

        return $command_uuids;
    }

    public function enqueuePersonDisableForMember(Member $member, AccessIdentity $identity): int
    {
        $payload = [
            'device_user_id' => $identity->device_user_id,
        ];

        return $this->enqueueForBranchDevices(
            branch_id: $member->branch_id,
            type: AccessControlDeviceCommand::TYPE_PERSON_DISABLE,
            payload: $payload,
            subject_type: AccessIdentity::SUBJECT_MEMBER,
            subject_id: $member->id,
            priority: 20,
        );
    }

    public function enqueuePersonDeleteForMember(Member $member, AccessIdentity $identity): int
    {
        $payload = [
            'device_user_id' => $identity->device_user_id,
        ];

        return $this->enqueueForBranchDevices(
            branch_id: $member->branch_id,
            type: AccessControlDeviceCommand::TYPE_PERSON_DELETE,
            payload: $payload,
            subject_type: AccessIdentity::SUBJECT_MEMBER,
            subject_id: $member->id,
            priority: 30,
        );
    }

    public function enqueueLogsPull(AccessControlDevice $device, ?CarbonInterface $from_time = null, ?int $limit = null): AccessControlDeviceCommand
    {
        $payload = [
            'from_time' => $from_time?->toIso8601String(),
            'limit' => $limit,
        ];

        return $this->enqueueDeviceCommand(
            device: $device,
            type: AccessControlDeviceCommand::TYPE_LOGS_PULL,
            payload: $payload,
            subject_type: null,
            subject_id: null,
            priority: 5,
        );
    }

    /**
     * Enqueue user sync command for a member.
     * This creates/updates the user on the device via person_upsert.
     * Fingerprint capture is done manually via Hikvision web dashboard.
     *
     * @return array{command: AccessControlDeviceCommand, access_identity: AccessIdentity}
     */
    public function enqueueUserSync(
        Member $member,
        AccessIdentity $access_identity,
        Carbon $valid_from,
        Carbon $valid_until,
    ): array {
        $device = AccessControlDevice::query()
            ->withoutBranchScope()
            ->where('branch_id', $member->branch_id)
            ->forIntegration(AccessControlDevice::INTEGRATION_HIKVISION)
            ->active()
            ->first();

        if (!$device) {
            throw new AccessControlActionException('No active access control device found for this branch.');
        }

        $access_logger = app(AccessLogger::class);

        $payload = [
            'access_identity_id' => $access_identity->id,
            'device_user_id' => $access_identity->device_user_id,
            'full_name' => $member->full_name,
            'subject_type' => AccessIdentity::SUBJECT_MEMBER,
            'subject_id' => $member->id,
            'valid_from' => $valid_from->toIso8601String(),
            'valid_to' => $valid_until->endOfDay()->toIso8601String(),
            'reason' => 'user_sync_button',
        ];

        $command = $this->enqueueDeviceCommand(
            device: $device,
            type: AccessControlDeviceCommand::TYPE_PERSON_UPSERT,
            payload: $payload,
            subject_type: AccessIdentity::SUBJECT_MEMBER,
            subject_id: $member->id,
            priority: 10,
        );

        $access_logger->info('user_sync_initiated', [
            'command_id' => $command->id,
            'branch_id' => $member->branch_id,
            'member_id' => $member->id,
            'member_no' => $member->member_no,
            'device_user_id' => $access_identity->device_user_id,
            'device_id' => $device->id,
            'valid_from' => $valid_from->toDateString(),
            'valid_until' => $valid_until->toDateString(),
        ]);

        return [
            'command' => $command,
            'access_identity' => $access_identity,
        ];
    }

    /**
     * Legacy method for backward compatibility.
     * @deprecated Use enqueueUserSync() instead
     */
    public function enqueueFingerprintEnrollment(
        Member $member,
        AccessIdentity $access_identity,
        Carbon $valid_from,
        Carbon $valid_until,
    ): array {
        return $this->enqueueUserSync($member, $access_identity, $valid_from, $valid_until);
    }

    // -------------------------------------------------------------------------
    // Staff (User) Methods
    // -------------------------------------------------------------------------

    /**
     * Enqueue user sync command for a staff member.
     * This creates/updates the user on the device via person_upsert.
     * Staff have long-term access (no subscription-based end date).
     *
     * @return array{command: AccessControlDeviceCommand, access_identity: AccessIdentity}
     */
    public function enqueueStaffUserSync(
        User $user,
        AccessIdentity $access_identity,
        Carbon $valid_from,
        Carbon $valid_until,
    ): array {
        $device = AccessControlDevice::query()
            ->withoutBranchScope()
            ->where('branch_id', $user->branch_id)
            ->forIntegration(AccessControlDevice::INTEGRATION_HIKVISION)
            ->active()
            ->first();

        if (!$device) {
            throw new AccessControlActionException('No active access control device found for this branch.');
        }

        $access_logger = app(AccessLogger::class);

        $payload = [
            'access_identity_id' => $access_identity->id,
            'device_user_id' => $access_identity->device_user_id,
            'full_name' => $user->name,
            'subject_type' => AccessIdentity::SUBJECT_STAFF,
            'subject_id' => $user->id,
            'valid_from' => $valid_from->toIso8601String(),
            'valid_to' => $valid_until->endOfDay()->toIso8601String(),
            'reason' => 'staff_sync_button',
        ];

        $command = $this->enqueueDeviceCommand(
            device: $device,
            type: AccessControlDeviceCommand::TYPE_PERSON_UPSERT,
            payload: $payload,
            subject_type: AccessIdentity::SUBJECT_STAFF,
            subject_id: $user->id,
            priority: 10,
        );

        $access_logger->info('staff_sync_initiated', [
            'command_id' => $command->id,
            'branch_id' => $user->branch_id,
            'user_id' => $user->id,
            'user_name' => $user->name,
            'device_user_id' => $access_identity->device_user_id,
            'device_id' => $device->id,
            'valid_from' => $valid_from->toDateString(),
            'valid_until' => $valid_until->toDateString(),
        ]);

        return [
            'command' => $command,
            'access_identity' => $access_identity,
        ];
    }

    /**
     * Enqueue disable command for a staff member.
     */
    public function enqueueDisableFingerprintForStaff(User $user, User $actor): array
    {
        $device_user_id = 'STAFF-' . $user->id;

        // Use last week for both valid_from and valid_to to ensure user is clearly expired
        $last_week = Carbon::now()->subWeek()->startOfDay();
        $valid_from = $last_week->format('Y-m-d H:i:s');
        $valid_to = $last_week->copy()->endOfDay()->format('Y-m-d H:i:s');

        $access_logger = app(AccessLogger::class);
        $access_logger->info('staff_fingerprint_disable_requested', [
            'branch_id' => $user->branch_id,
            'user_id' => $user->id,
            'user_name' => $user->name,
            'device_user_id' => $device_user_id,
            'actor_user_id' => $actor->id,
            'valid_from' => $valid_from,
            'valid_to' => $valid_to,
        ]);

        return $this->enqueueAccessSetValidityForStaff($user, $valid_from, $valid_to, [
            'user_name' => $user->name,
            'device_user_id' => $device_user_id,
            'actor_user_id' => $actor->id,
            'valid_from' => $valid_from,
        ]);
    }

    /**
     * Enqueue enable command for a staff member (long-term access).
     */
    public function enqueueEnableFingerprintForStaff(User $user, User $actor): array
    {
        $device_user_id = 'STAFF-' . $user->id;

        // Staff have long-term access (10 years)
        $valid_from = Carbon::now()->subMinutes(1)->format('Y-m-d H:i:s');
        $valid_to = Carbon::now()->addYears(10)->endOfDay()->format('Y-m-d H:i:s');

        $access_logger = app(AccessLogger::class);
        $access_logger->info('staff_fingerprint_enable_requested', [
            'branch_id' => $user->branch_id,
            'user_id' => $user->id,
            'user_name' => $user->name,
            'device_user_id' => $device_user_id,
            'actor_user_id' => $actor->id,
            'valid_from' => $valid_from,
            'valid_to' => $valid_to,
        ]);

        return $this->enqueueAccessSetValidityForStaff($user, $valid_from, $valid_to, [
            'user_name' => $user->name,
            'device_user_id' => $device_user_id,
            'actor_user_id' => $actor->id,
            'valid_from' => $valid_from,
        ]);
    }

    /**
     * Enqueue access_set_validity for a staff member across active devices.
     *
     * @return array<int, string> created command UUIDs
     */
    public function enqueueAccessSetValidityForStaff(User $user, ?string $valid_from, ?string $valid_to, array $log_context = []): array
    {
        $device_user_id = 'STAFF-' . $user->id;

        $devices = AccessControlDevice::query()
            ->withoutBranchScope()
            ->where('branch_id', $user->branch_id)
            ->forIntegration(AccessControlDevice::INTEGRATION_HIKVISION)
            ->active()
            ->get();

        $payload = [
            'device_user_id' => $device_user_id,
            'valid_from' => $valid_from,
            'valid_to' => $valid_to,
        ];

        $access_logger = app(AccessLogger::class);

        $command_uuids = [];

        foreach ($devices as $device) {
            $command = $this->enqueueDeviceCommand(
                device: $device,
                type: AccessControlDeviceCommand::TYPE_ACCESS_SET_VALIDITY,
                payload: $payload,
                subject_type: AccessIdentity::SUBJECT_STAFF,
                subject_id: $user->id,
                priority: 20,
                available_at: now(),
            );

            $command_uuids[] = $command->id;

            $access_logger->info('staff_command_enqueued', [
                'command_uuid' => $command->id,
                'device_id' => $device->id,
                'branch_id' => $device->branch_id,
                'user_id' => $user->id,
                'valid_to' => $valid_to,
                ...$log_context,
            ]);
        }

        return $command_uuids;
    }

    /**
     * Enqueue person_delete for a staff member across active devices.
     */
    public function enqueuePersonDeleteForStaff(User $user, AccessIdentity $identity): int
    {
        $payload = [
            'device_user_id' => $identity->device_user_id,
        ];

        return $this->enqueueForBranchDevices(
            branch_id: $user->branch_id,
            type: AccessControlDeviceCommand::TYPE_PERSON_DELETE,
            payload: $payload,
            subject_type: AccessIdentity::SUBJECT_STAFF,
            subject_id: $user->id,
            priority: 30,
        );
    }
}
