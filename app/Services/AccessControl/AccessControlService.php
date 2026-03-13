<?php

namespace App\Services\AccessControl;

use App\Models\AccessIdentity;
use App\Models\AccessControlDevice;
use App\Models\Member;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AccessControlService
{
    /**
     * Resolve the stable device_user_id for a member.
     *
     * Cloud canonical rule:
     * - members: device_user_id = members.member_no
     */
    public function getMemberDeviceUserId(Member $member): string
    {
        $device_user_id = (string) ($member->member_no ?? '');

        if ($device_user_id === '') {
            throw new \RuntimeException('Member is missing member_no, cannot build device_user_id.');
        }

        return $device_user_id;
    }

    /**
     * Enroll a member for fingerprint access.
     * Creates AccessIdentity and enqueues commands for the Local Agent.
     *
     * @param Member $member The member to enroll
     * @param string $access_type 'subscription' or 'insurance' to determine validity dates
     * @param int|null $policy_id Optional MemberInsurance ID for insurance-based access
     */
    public function enrollMemberFingerprint(
        Member $member,
        string $access_type = 'subscription',
        ?int $policy_id = null
    ): array {
        $branch_id = $member->branch_id;
        $command_service = app(AccessControlCommandService::class);

        // Check if member already has an AccessIdentity (including soft-deleted)
        $existing_identity = AccessIdentity::withTrashed()
            ->withoutBranchScope()
            ->where('branch_id', $branch_id)
            ->where('integration_type', AccessControlDevice::INTEGRATION_HIKVISION)
            ->where('subject_type', AccessIdentity::SUBJECT_MEMBER)
            ->where('subject_id', $member->id)
            ->first();

        if ($existing_identity) {
            // If it was soft-deleted, restore it
            if ($existing_identity->trashed()) {
                $existing_identity->restore();
                $existing_identity->update(['is_active' => true]);

                // Re-sync to devices with updated validity (via local agent)
                $validity = $this->getValidityDates($member, $access_type, $policy_id);
                if ($validity['valid']) {
                    $command_service->enqueuePersonUpsertForMember($member, $existing_identity, $validity);
                    $command_service->enqueueAccessSetValidityForMember(
                        $member,
                        $validity['start_date']?->toIso8601String(),
                        $validity['end_date']?->copy()->endOfDay()->toIso8601String(),
                    );
                }

                return [
                    'success' => true,
                    'message' => __('Fingerprint access restored. Employee No: :id', ['id' => $existing_identity->device_user_id]),
                    'identity' => $existing_identity,
                    'device_user_id' => $existing_identity->device_user_id,
                ];
            }

            return [
                'success' => false,
                'message' => __('Member already has fingerprint access registered. Employee No: :id', ['id' => $existing_identity->device_user_id]),
                'identity' => $existing_identity,
            ];
        }

        // Get validity dates based on access type
        $validity = $this->getValidityDates($member, $access_type, $policy_id);

        if (!$validity['valid']) {
            return [
                'success' => false,
                'message' => $validity['message'],
            ];
        }

        try {
            DB::beginTransaction();

            // Stable device user ID (member_no)
            $device_user_id = $this->getMemberDeviceUserId($member);

            // Create AccessIdentity
            $identity = AccessIdentity::create([
                'branch_id' => $branch_id,
                'integration_type' => AccessControlDevice::INTEGRATION_HIKVISION,
                'provider' => AccessControlDevice::PROVIDER_HIKVISION_AGENT,
                'subject_type' => AccessIdentity::SUBJECT_MEMBER,
                'subject_id' => $member->id,
                'device_user_id' => $device_user_id,
                'is_active' => true,
            ]);

            // Enqueue commands; Local Agent will execute on LAN.
            $command_service->enqueuePersonUpsertForMember($member, $identity, $validity);
            $command_service->enqueueAccessSetValidityForMember(
                $member,
                $validity['start_date']?->toIso8601String(),
                $validity['end_date']?->copy()->endOfDay()->toIso8601String(),
            );

            DB::commit();

            Log::info('Member enrolled for fingerprint access', [
                'member_id' => $member->id,
                'device_user_id' => $device_user_id,
                'branch_id' => $branch_id,
                'access_type' => $access_type,
            ]);

            return [
                'success' => true,
                'message' => __('Fingerprint access registered. Device sync enqueued. Employee No: :id', ['id' => $device_user_id]),
                'identity' => $identity,
                'device_user_id' => $device_user_id,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to enroll member for fingerprint access', [
                'member_id' => $member->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => __('Failed to register fingerprint access: :error', ['error' => $e->getMessage()]),
            ];
        }
    }

    /**
     * Get validity dates based on member's subscription or insurance.
     * Start date is set to 00:00:00 and end date is set to 23:59:59.
     */
    protected function getValidityDates(Member $member, string $access_type, ?int $policy_id): array
    {
        // Eligibility logic MUST live in AccessEligibilityService only.
        // This method only converts the eligibility decision into a validity window for device sync commands.
        $eligibility = app(AccessEligibilityService::class);
        $date = now();

        if (!$eligibility->is_member_allowed($member, $date)) {
            return [
                'valid' => false,
                'message' => __('Member has no active subscription or insurance.'),
            ];
        }

        return [
            'valid' => true,
            'start_date' => Carbon::parse($date)->startOfDay(),
            'end_date' => $eligibility->allowed_until($member, $date),
        ];
    }

    /**
     * Remove a member's fingerprint access.
     */
    public function removeMemberFingerprint(Member $member): array
    {
        $identity = AccessIdentity::query()
            ->withoutBranchScope()
            ->where('integration_type', AccessControlDevice::INTEGRATION_HIKVISION)
            ->where('subject_type', AccessIdentity::SUBJECT_MEMBER)
            ->where('subject_id', $member->id)
            ->first();

        if (!$identity) {
            return [
                'success' => false,
                'message' => __('Member does not have fingerprint access registered.'),
            ];
        }

        try {
            DB::beginTransaction();

            // Enqueue delete; local agent executes on LAN.
            app(AccessControlCommandService::class)->enqueuePersonDeleteForMember($member, $identity);

            // Delete the identity
            $identity->delete();

            DB::commit();

            Log::info('Member fingerprint access removed', [
                'member_id' => $member->id,
                'device_user_id' => $identity->device_user_id,
            ]);

            return [
                'success' => true,
                'message' => __('Fingerprint access removed. Device delete enqueued.'),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to remove member fingerprint access', [
                'member_id' => $member->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => __('Failed to remove fingerprint access: :error', ['error' => $e->getMessage()]),
            ];
        }
    }

    /**
     * Update member's validity on the device (e.g., after subscription renewal).
     * Updates validity period according to member's active subscription end date.
     */
    public function updateMemberValidity(Member $member): array
    {
        $identity = AccessIdentity::query()
            ->withoutBranchScope()
            ->where('integration_type', AccessControlDevice::INTEGRATION_HIKVISION)
            ->where('subject_type', AccessIdentity::SUBJECT_MEMBER)
            ->where('subject_id', $member->id)
            ->active()
            ->first();

        if (!$identity) {
            return [
                'success' => false,
                'message' => __('Member does not have active fingerprint access.'),
            ];
        }

        $validity = $this->getValidityDates($member, 'subscription', null);

        if (!$validity['valid']) {
            return [
                'success' => false,
                'message' => $validity['message'],
            ];
        }

        app(AccessControlCommandService::class)->enqueueAccessSetValidityForMember(
            $member,
            $validity['start_date']?->toIso8601String(),
            $validity['end_date']?->copy()->endOfDay()->toIso8601String(),
        );

        $validity_end_date = $validity['end_date']->format('d M Y');

        Log::info('Member fingerprint validity update enqueued', [
            'member_id' => $member->id,
            'device_user_id' => $identity->device_user_id,
            'validity_end' => $validity_end_date,
        ]);

        return [
            'success' => true,
            'message' => __('Validity update enqueued. Valid until :date (device updates when agent syncs).', ['date' => $validity_end_date]),
        ];
    }

    /**
     * Enable a member's fingerprint access on the device.
     * Updates validity according to member's active subscription end date.
     */
    public function enableMemberFingerprint(Member $member): array
    {
        $identity = AccessIdentity::query()
            ->withoutBranchScope()
            ->where('integration_type', AccessControlDevice::INTEGRATION_HIKVISION)
            ->where('subject_type', AccessIdentity::SUBJECT_MEMBER)
            ->where('subject_id', $member->id)
            ->first();

        if (!$identity) {
            return [
                'success' => false,
                'message' => __('Member does not have fingerprint access registered.'),
            ];
        }

        if ($identity->is_active) {
            return [
                'success' => false,
                'message' => __('Fingerprint access is already enabled.'),
            ];
        }

        $validity = $this->getValidityDates($member, 'subscription', null);
        if (!$validity['valid']) {
            return [
                'success' => false,
                'message' => $validity['message'],
            ];
        }

        try {
            $command_service = app(AccessControlCommandService::class);
            $command_service->enqueuePersonUpsertForMember($member, $identity, $validity);
            $command_service->enqueueAccessSetValidityForMember(
                $member,
                $validity['start_date']?->toIso8601String(),
                $validity['end_date']?->copy()->endOfDay()->toIso8601String(),
            );

            $identity->update(['is_active' => true]);

            $validity_end_date = $validity['end_date']->format('d M Y');

            Log::info('Member fingerprint access enabled', [
                'member_id' => $member->id,
                'device_user_id' => $identity->device_user_id,
                'validity_end' => $validity_end_date,
            ]);

            return [
                'success' => true,
                'message' => __('Fingerprint access enabled. Device sync enqueued (valid until :date).', ['date' => $validity_end_date]),
            ];
        } catch (\Exception $e) {
            Log::error('Failed to enable member fingerprint access', [
                'member_id' => $member->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => __('Failed to enable fingerprint access: :error', ['error' => $e->getMessage()]),
            ];
        }
    }

    /**
     * Disable a member's fingerprint access by setting validity to the previous day.
     * Keeps the fingerprint data on the device, just updates validity end date to yesterday.
     */
    public function disableMemberFingerprint(Member $member): array
    {
        $identity = AccessIdentity::query()
            ->withoutBranchScope()
            ->where('integration_type', AccessControlDevice::INTEGRATION_HIKVISION)
            ->where('subject_type', AccessIdentity::SUBJECT_MEMBER)
            ->where('subject_id', $member->id)
            ->first();

        if (!$identity) {
            return [
                'success' => false,
                'message' => __('Member does not have fingerprint access registered.'),
            ];
        }

        if (!$identity->is_active) {
            return [
                'success' => false,
                'message' => __('Fingerprint access is already disabled.'),
            ];
        }

        try {
            $yesterday_end = \Carbon\Carbon::yesterday()->endOfDay()->format('Y-m-d H:i:s');

            app(AccessControlCommandService::class)->enqueueAccessSetValidityForMember(
                $member,
                null,
                $yesterday_end,
            );

            $identity->update(['is_active' => false]);

            $expired_date = \Carbon\Carbon::parse($yesterday_end)->format('d M Y');

            Log::info('Member fingerprint access disabled', [
                'member_id' => $member->id,
                'device_user_id' => $identity->device_user_id,
                'validity_expired_at' => $expired_date,
            ]);

            return [
                'success' => true,
                'message' => __('Fingerprint access disabled. Device validity set to expired (yesterday).'),
            ];
        } catch (\Exception $e) {
            Log::error('Failed to disable member fingerprint access', [
                'member_id' => $member->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => __('Failed to disable fingerprint access: :error', ['error' => $e->getMessage()]),
            ];
        }
    }

    /**
     * Disable fingerprint access for all members with expired subscriptions/insurance.
     * Returns count of disabled members.
     */
    public function disableExpiredMemberAccess(): array
    {
        $disabled_count = 0;
        $errors = [];

        // Get all active access identities for members (don't use ->with('member') due to relationship issue)
        $active_identities = AccessIdentity::query()
            ->withoutBranchScope()
            ->where('integration_type', AccessControlDevice::INTEGRATION_HIKVISION)
            ->where('subject_type', AccessIdentity::SUBJECT_MEMBER)
            ->where('is_active', true)
            ->get();

        foreach ($active_identities as $identity) {
            // Manually load the member
            $member = Member::find($identity->subject_id);
            if (!$member) {
                Log::warning('AccessIdentity references non-existent member', [
                    'identity_id' => $identity->id,
                    'subject_id' => $identity->subject_id,
                ]);
                continue;
            }

            // Check if member has valid subscription or insurance
            $validity = $this->getValidityDates($member, 'subscription', null);

            if (!$validity['valid']) {
                // No valid subscription/insurance - disable access
                $result = $this->disableMemberFingerprint($member);

                if ($result['success']) {
                    $disabled_count++;
                    Log::info('Disabled expired member fingerprint access', [
                        'member_id' => $member->id,
                        'member_name' => $member->full_name,
                    ]);
                } else {
                    $errors[] = "Member {$member->id}: " . $result['message'];
                }
            }
        }

        return [
            'disabled_count' => $disabled_count,
            'errors' => $errors,
        ];
    }

    /**
     * Check if a member has fingerprint access registered.
     */
    public function hasFingerprintAccess(Member $member): bool
    {
        return AccessIdentity::query()
            ->withoutBranchScope()
            ->where('integration_type', AccessControlDevice::INTEGRATION_HIKVISION)
            ->where('subject_type', AccessIdentity::SUBJECT_MEMBER)
            ->where('subject_id', $member->id)
            ->active()
            ->exists();
    }

    /**
     * Get member's AccessIdentity if exists (including inactive).
     */
    public function getMemberIdentity(Member $member): ?AccessIdentity
    {
        return AccessIdentity::query()
            ->withoutBranchScope()
            ->where('integration_type', AccessControlDevice::INTEGRATION_HIKVISION)
            ->where('subject_type', AccessIdentity::SUBJECT_MEMBER)
            ->where('subject_id', $member->id)
            ->first();
    }

    // -------------------------------------------------------------------------
    // Staff (User) Methods
    // -------------------------------------------------------------------------

    /**
     * Resolve the stable device_user_id for a staff member.
     *
     * Cloud canonical rule:
     * - staff: device_user_id = STAFF-{users.id}
     */
    public function getStaffDeviceUserId(\App\Models\User $user): string
    {
        return 'STAFF-' . $user->id;
    }

    /**
     * Get staff's AccessIdentity if exists (including inactive).
     */
    public function getStaffIdentity(\App\Models\User $user): ?AccessIdentity
    {
        return AccessIdentity::query()
            ->withoutBranchScope()
            ->where('integration_type', AccessControlDevice::INTEGRATION_HIKVISION)
            ->where('subject_type', AccessIdentity::SUBJECT_STAFF)
            ->where('subject_id', $user->id)
            ->first();
    }

    /**
     * Check if a staff member has fingerprint access registered.
     */
    public function staffHasFingerprintAccess(\App\Models\User $user): bool
    {
        return AccessIdentity::query()
            ->withoutBranchScope()
            ->where('integration_type', AccessControlDevice::INTEGRATION_HIKVISION)
            ->where('subject_type', AccessIdentity::SUBJECT_STAFF)
            ->where('subject_id', $user->id)
            ->active()
            ->exists();
    }

    /**
     * Remove a staff member's fingerprint access.
     */
    public function removeStaffFingerprint(\App\Models\User $user): array
    {
        $identity = AccessIdentity::query()
            ->withoutBranchScope()
            ->where('integration_type', AccessControlDevice::INTEGRATION_HIKVISION)
            ->where('subject_type', AccessIdentity::SUBJECT_STAFF)
            ->where('subject_id', $user->id)
            ->first();

        if (!$identity) {
            return [
                'success' => false,
                'message' => __('Staff does not have fingerprint access registered.'),
            ];
        }

        try {
            DB::beginTransaction();

            // Enqueue delete; local agent executes on LAN.
            app(AccessControlCommandService::class)->enqueuePersonDeleteForStaff($user, $identity);

            // Delete the identity
            $identity->delete();

            DB::commit();

            Log::info('Staff fingerprint access removed', [
                'user_id' => $user->id,
                'device_user_id' => $identity->device_user_id,
            ]);

            return [
                'success' => true,
                'message' => __('Fingerprint access removed. Device delete enqueued.'),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to remove staff fingerprint access', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => __('Failed to remove fingerprint access: :error', ['error' => $e->getMessage()]),
            ];
        }
    }

    /**
     * Enable a staff member's fingerprint access on the device.
     * Staff have long-term access (10 years).
     */
    public function enableStaffFingerprint(\App\Models\User $user): array
    {
        $identity = AccessIdentity::query()
            ->withoutBranchScope()
            ->where('integration_type', AccessControlDevice::INTEGRATION_HIKVISION)
            ->where('subject_type', AccessIdentity::SUBJECT_STAFF)
            ->where('subject_id', $user->id)
            ->first();

        if (!$identity) {
            return [
                'success' => false,
                'message' => __('Staff does not have fingerprint access registered.'),
            ];
        }

        if ($identity->is_active) {
            return [
                'success' => false,
                'message' => __('Fingerprint access is already enabled.'),
            ];
        }

        try {
            $command_service = app(AccessControlCommandService::class);

            // Staff have long-term access
            $valid_from = Carbon::now()->subMinutes(1);
            $valid_until = Carbon::now()->addYears(10);

            $command_service->enqueueAccessSetValidityForStaff(
                $user,
                $valid_from->format('Y-m-d H:i:s'),
                $valid_until->endOfDay()->format('Y-m-d H:i:s'),
            );

            $identity->update(['is_active' => true, 'valid_until' => $valid_until]);

            Log::info('Staff fingerprint access enabled', [
                'user_id' => $user->id,
                'device_user_id' => $identity->device_user_id,
                'validity_end' => $valid_until->format('d M Y'),
            ]);

            return [
                'success' => true,
                'message' => __('Fingerprint access enabled. Staff has long-term access.'),
            ];
        } catch (\Exception $e) {
            Log::error('Failed to enable staff fingerprint access', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => __('Failed to enable fingerprint access: :error', ['error' => $e->getMessage()]),
            ];
        }
    }

    /**
     * Disable a staff member's fingerprint access by setting validity to yesterday.
     */
    public function disableStaffFingerprint(\App\Models\User $user): array
    {
        $identity = AccessIdentity::query()
            ->withoutBranchScope()
            ->where('integration_type', AccessControlDevice::INTEGRATION_HIKVISION)
            ->where('subject_type', AccessIdentity::SUBJECT_STAFF)
            ->where('subject_id', $user->id)
            ->first();

        if (!$identity) {
            return [
                'success' => false,
                'message' => __('Staff does not have fingerprint access registered.'),
            ];
        }

        if (!$identity->is_active) {
            return [
                'success' => false,
                'message' => __('Fingerprint access is already disabled.'),
            ];
        }

        try {
            $yesterday_end = \Carbon\Carbon::yesterday()->endOfDay()->format('Y-m-d H:i:s');

            app(AccessControlCommandService::class)->enqueueAccessSetValidityForStaff(
                $user,
                null,
                $yesterday_end,
            );

            $identity->update(['is_active' => false]);

            $expired_date = \Carbon\Carbon::parse($yesterday_end)->format('d M Y');

            Log::info('Staff fingerprint access disabled', [
                'user_id' => $user->id,
                'device_user_id' => $identity->device_user_id,
                'validity_expired_at' => $expired_date,
            ]);

            return [
                'success' => true,
                'message' => __('Fingerprint access disabled. Device validity set to expired (yesterday).'),
            ];
        } catch (\Exception $e) {
            Log::error('Failed to disable staff fingerprint access', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => __('Failed to disable fingerprint access: :error', ['error' => $e->getMessage()]),
            ];
        }
    }
}
