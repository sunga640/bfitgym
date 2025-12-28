<?php

namespace App\Livewire\Members;

use App\Exceptions\AccessControl\AccessControlActionException;
use App\Models\AccessControlDeviceCommand;
use App\Models\AccessIdentity;
use App\Models\Member;
use App\Models\MembershipPackage;
use App\Services\AccessControl\AccessControlCommandService;
use App\Services\AccessControl\AccessEligibilityService;
use App\Services\AccessControl\AccessControlService;
use App\Services\Memberships\SubscriptionService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;
use RuntimeException;
use Throwable;

class Show extends Component
{
    public Member $member;
    public bool $has_fingerprint_access = false;
    public bool $has_fingerprint_registered = false;
    public ?AccessIdentity $access_identity = null;

    // Subscription modal properties
    public bool $show_subscription_modal = false;
    public ?int $membership_package_id = null;
    public string $start_date = '';
    public string $end_date = '';
    public bool $auto_renew = false;
    public string $subscription_notes = '';
    public string $amount = '';
    public string $currency = '';
    public string $payment_method = 'cash';
    public ?string $reference = '';
    public string $paid_at = '';

    public function mount(Member $member): void
    {
        $this->member = $member->load(['branch', 'subscriptions.membershipPackage', 'insurances.insurer']);
        $this->authorize('view', $member);

        $this->loadFingerprintStatus();
        $this->initSubscriptionDefaults();
    }

    protected function initSubscriptionDefaults(): void
    {
        $this->currency = app_currency();
        $this->start_date = now()->format('Y-m-d');
        $this->paid_at = now()->format('Y-m-d\TH:i');
    }

    protected function loadFingerprintStatus(): void
    {
        $service = app(AccessControlService::class);
        $this->access_identity = $service->getMemberIdentity($this->member);
        
        // A user is "registered" if identity exists AND was synced to device
        $this->has_fingerprint_registered = $this->access_identity !== null 
            && $this->access_identity->device_synced_at !== null;
        
        // Access is active only if synced AND is_active
        $this->has_fingerprint_access = $this->has_fingerprint_registered 
            && ($this->access_identity?->is_active ?? false);
    }

    /**
     * Sync user to access control device.
     * Creates AccessIdentity (active) and enqueues person_upsert command for local agent.
     * Fingerprint capture is done manually via Hikvision web dashboard.
     */
    public function syncUserToDevice(): void
    {
        $this->authorize('update', $this->member);

        // Validate: member must have active subscription
        $active_subscription = $this->member->getActiveSubscription();
        if (!$active_subscription) {
            session()->flash('error', __('Member must have an active subscription before syncing to device.'));
            return;
        }

        // Check for existing identity that's already synced
        $existing_identity = $this->access_identity;
        if ($existing_identity && $existing_identity->device_synced_at !== null) {
            session()->flash('error', __('Member is already synced to device.'));
            return;
        }

        $access_logger = app(\App\Support\AccessLogger::class);

        try {
            $device_user_id = trim((string) $this->member->member_no);
            if ($device_user_id === '') {
                session()->flash('error', __('Member is missing member number; cannot sync to device.'));
                return;
            }

            $valid_from = now();
            $valid_until = $active_subscription->end_date;

            // Force delete any existing AccessIdentity that could conflict (including soft-deleted)
            // SoftDeletes only sets deleted_at, but unique constraint ignores it
            AccessIdentity::withTrashed()
                ->where('branch_id', $this->member->branch_id)
                ->where(function ($query) use ($device_user_id) {
                    $query->where('device_user_id', $device_user_id)
                        ->orWhere(function ($q) {
                            $q->where('subject_type', AccessIdentity::SUBJECT_MEMBER)
                                ->where('subject_id', $this->member->id);
                        });
                })
                ->forceDelete();

            // Create fresh AccessIdentity (active immediately - fingerprint added later via device UI)
            $access_identity = AccessIdentity::create([
                'branch_id' => $this->member->branch_id,
                'device_user_id' => $device_user_id,
                'subject_type' => AccessIdentity::SUBJECT_MEMBER,
                'subject_id' => $this->member->id,
                'is_active' => true,
                'fingerprint_enrolled_at' => null,
                'valid_from' => $valid_from,
                'valid_until' => $valid_until,
                'last_sync_error' => null,
            ]);

            // Enqueue user sync command
            $result = app(AccessControlCommandService::class)->enqueueUserSync(
                member: $this->member,
                access_identity: $access_identity,
                valid_from: $valid_from,
                valid_until: $valid_until,
            );

            $access_logger->info('user_sync_to_device_started', [
                'member_id' => $this->member->id,
                'member_no' => $this->member->member_no,
                'access_identity_id' => $access_identity->id,
                'command_id' => $result['command']->id,
            ]);

            $this->loadFingerprintStatus();

            session()->flash('success', __('User sync initiated. The member will be added to the device. Add fingerprint via device web dashboard.'));
        } catch (AccessControlActionException $e) {
            $access_logger->error('user_sync_to_device_failed', [
                'member_id' => $this->member->id,
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', $e->getMessage());
        } catch (\Throwable $e) {
            report($e);
            $access_logger->error('user_sync_to_device_failed', [
                'member_id' => $this->member->id,
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', __('Failed to sync user to device. Please try again.'));
        }
    }

    /**
     * Legacy method for backward compatibility.
     * @deprecated Use syncUserToDevice() instead
     */
    public function addFingerprint(): void
    {
        $this->syncUserToDevice();
    }

    /**
     * Enable a previously disabled access.
     * Restores validity to the member's current subscription end date.
     */
    public function enableFingerprint(): void
    {
        $this->authorize('update', $this->member);

        $access_logger = app(\App\Support\AccessLogger::class);

        if (!$this->access_identity) {
            session()->flash('error', __('Member does not have device access registered.'));
            return;
        }

        // Must have been synced to device to enable
        if ($this->access_identity->device_synced_at === null) {
            session()->flash('error', __('User has not been synced to device. Please sync first.'));
            return;
        }

        // Check for active subscription
        $active_subscription = $this->member->getActiveSubscription();
        if (!$active_subscription) {
            session()->flash('error', __('Member must have an active subscription to enable device access.'));
            return;
        }

        try {
            $valid_until = $active_subscription->end_date;

            // Update local state
            $this->access_identity->update([
                'is_active' => true,
                'valid_until' => $valid_until,
                'disabled_at' => null,
                'last_sync_error' => null,
            ]);

            // Enqueue command to update device
            $command_uuids = app(AccessControlCommandService::class)
                ->enqueueEnableFingerprintForMember($this->member, auth()->user());

            $access_logger->info('device_access_enabled', [
                'member_id' => $this->member->id,
                'member_no' => $this->member->member_no,
                'access_identity_id' => $this->access_identity->id,
                'valid_until' => $valid_until->toDateString(),
                'actor_user_id' => auth()->id(),
                'command_count' => count($command_uuids),
            ]);

            session()->flash('success', __('Device access enabled. Valid until :date.', [
                'date' => $valid_until->format('d M Y'),
            ]));
            $this->loadFingerprintStatus();
        } catch (AccessControlActionException $e) {
            $access_logger->error('device_access_enable_failed', [
                'member_id' => $this->member->id,
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', $e->getMessage());
        } catch (\Throwable $e) {
            report($e);
            $access_logger->error('device_access_enable_failed', [
                'member_id' => $this->member->id,
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', __('Failed to enable device access. Please try again.'));
        }
    }

    /**
     * Disable device access by setting validity to yesterday.
     * Stores original valid_until for potential re-enable.
     */
    public function disableFingerprint(): void
    {
        $this->authorize('update', $this->member);

        $access_logger = app(\App\Support\AccessLogger::class);

        if (!$this->access_identity) {
            session()->flash('error', __('Member does not have device access registered.'));
            return;
        }

        if (!$this->access_identity->is_active) {
            session()->flash('error', __('Device access is already disabled.'));
            return;
        }

        try {
            // Store original valid_until before disabling
            $original_valid_until = $this->access_identity->valid_until;

            // Use last week for consistency with the device command
            // This ensures no timezone edge cases between cloud DB and device
            $last_week = now()->subWeek()->startOfDay();

            // Update local state
            $this->access_identity->update([
                'is_active' => false,
                'original_valid_until' => $original_valid_until,
                'valid_until' => $last_week->toDateString(),
                'disabled_at' => now(),
            ]);

            // Enqueue command to update device
            $command_uuids = app(AccessControlCommandService::class)
                ->enqueueDisableFingerprintForMember($this->member, auth()->user());

            $access_logger->info('device_access_disabled', [
                'member_id' => $this->member->id,
                'member_no' => $this->member->member_no,
                'access_identity_id' => $this->access_identity->id,
                'original_valid_until' => $original_valid_until?->toDateString(),
                'actor_user_id' => auth()->id(),
                'command_count' => count($command_uuids),
            ]);

            session()->flash('success', __('Device access disabled.'));
            $this->loadFingerprintStatus();
        } catch (AccessControlActionException $e) {
            $access_logger->error('device_access_disable_failed', [
                'member_id' => $this->member->id,
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', $e->getMessage());
        } catch (\Throwable $e) {
            report($e);
            $access_logger->error('device_access_disable_failed', [
                'member_id' => $this->member->id,
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', __('Failed to disable device access. Please try again.'));
        }
    }

    #[Computed]
    public function eligibility_is_allowed(): bool
    {
        return app(AccessEligibilityService::class)->isAllowed($this->member, now());
    }

    #[Computed]
    public function allowed_until_human(): string
    {
        $service = app(AccessEligibilityService::class);
        if (! $service->isAllowed($this->member, now())) {
            return __('Not eligible (no active subscription/insurance)');
        }

        $allowed_until = $service->allowedUntil($this->member, now());
        if ($allowed_until === null) {
            return __('Open-ended (insurance)');
        }

        return __('Until :date', ['date' => $allowed_until->toDateString()]);
    }

    #[Computed]
    public function last_access_set_validity_command(): ?AccessControlDeviceCommand
    {
        return AccessControlDeviceCommand::query()
            ->where('branch_id', $this->member->branch_id)
            ->where('subject_type', 'member')
            ->where('subject_id', $this->member->id)
            ->where('type', AccessControlDeviceCommand::TYPE_ACCESS_SET_VALIDITY)
            ->latest('created_at')
            ->first();
    }

    #[Computed]
    public function last_command_status(): ?string
    {
        return $this->last_access_set_validity_command?->status;
    }

    #[Computed]
    public function last_command_action(): ?string
    {
        $cmd = $this->last_access_set_validity_command;
        if (! $cmd) {
            return null;
        }

        $payload = is_array($cmd->payload) ? $cmd->payload : [];
        $valid_to = $payload['valid_to'] ?? null;

        if ($valid_to === null) {
            return 'enable';
        }

        try {
            $vt = Carbon::parse((string) $valid_to);
            return $vt->isPast() ? 'disable' : 'enable';
        } catch (\Throwable) {
            return null;
        }
    }

    #[Computed]
    public function last_command_is_pending(): bool
    {
        $status = $this->last_command_status;
        return in_array($status, ['pending', 'claimed', 'processing'], true);
    }

    #[Computed]
    public function disabled_requested_recently(): bool
    {
        $cmd = $this->last_access_set_validity_command;
        if (! $cmd) {
            return false;
        }

        if (! $cmd->created_at || $cmd->created_at->lt(now()->subMinutes(10))) {
            return false;
        }

        if (! in_array($cmd->status, ['pending', 'claimed', 'processing', 'done'], true)) {
            return false;
        }

        return $this->last_command_action === 'disable';
    }

    #[Computed]
    public function last_command_failed(): bool
    {
        return $this->last_command_status === 'failed';
    }

    public function removeFingerprint(): void
    {
        $this->authorize('update', $this->member);

        $service = app(AccessControlService::class);
        $result = $service->removeMemberFingerprint($this->member);

        if ($result['success']) {
            session()->flash('success', $result['message']);
            $this->loadFingerprintStatus();
        } else {
            session()->flash('error', $result['message']);
        }
    }

    // -------------------------------------------------------------------------
    // Subscription Modal Methods
    // -------------------------------------------------------------------------

    public function openSubscriptionModal(): void
    {
        $this->authorize('create', \App\Models\MemberSubscription::class);

        $this->resetSubscriptionForm();
        $this->show_subscription_modal = true;
    }

    public function closeSubscriptionModal(): void
    {
        $this->show_subscription_modal = false;
        $this->resetSubscriptionForm();
    }

    protected function resetSubscriptionForm(): void
    {
        $this->membership_package_id = null;
        $this->start_date = now()->format('Y-m-d');
        $this->end_date = '';
        $this->auto_renew = false;
        $this->subscription_notes = '';
        $this->amount = '';
        $this->currency = app_currency();
        $this->payment_method = 'cash';
        $this->reference = '';
        $this->paid_at = now()->format('Y-m-d\TH:i');
        $this->resetValidation();
    }

    public function updatedMembershipPackageId(): void
    {
        $this->syncAmountFromPackage();
        $this->syncEndDate();
    }

    public function updatedStartDate(): void
    {
        $this->syncEndDate();
    }

    protected function syncAmountFromPackage(): void
    {
        $package = $this->selectedPackage;

        if ($package) {
            $this->amount = (string) $package->price;
        }
    }

    protected function syncEndDate(): void
    {
        $package = $this->selectedPackage;

        if (!$package || empty($this->start_date)) {
            $this->end_date = '';
            return;
        }

        $service = app(SubscriptionService::class);
        $start = \Illuminate\Support\Carbon::parse($this->start_date)->startOfDay();
        $end = $service->calculateEndDate($start, $package);
        $this->end_date = $end->format('Y-m-d');
    }

    #[Computed]
    public function selectedPackage(): ?MembershipPackage
    {
        if (!$this->membership_package_id) {
            return null;
        }

        return MembershipPackage::query()->find($this->membership_package_id);
    }

    #[Computed]
    public function hasActiveSubscription(): bool
    {
        return $this->member->subscriptions->where('status', 'active')->isNotEmpty();
    }

    #[Computed]
    public function canSyncToDevice(): bool
    {
        // Member must have an active subscription AND not already synced to device
        return $this->hasActiveSubscription && !$this->has_fingerprint_registered;
    }

    /**
     * Legacy computed property for backward compatibility.
     * @deprecated Use canSyncToDevice instead
     */
    #[Computed]
    public function canAddFingerprint(): bool
    {
        return $this->canSyncToDevice;
    }

    /**
     * Check if there's a pending sync (identity exists but not synced yet).
     */
    #[Computed]
    public function hasPendingSync(): bool
    {
        return $this->access_identity !== null 
            && $this->access_identity->device_synced_at === null;
    }

    /**
     * Legacy computed property for backward compatibility.
     * @deprecated Use hasPendingSync instead
     */
    #[Computed]
    public function hasPendingEnrollment(): bool
    {
        return $this->hasPendingSync;
    }

    /**
     * Get device sync status text for display.
     */
    #[Computed]
    public function deviceSyncStatusText(): string
    {
        if (!$this->access_identity) {
            return __('Not Synced');
        }

        if ($this->access_identity->device_synced_at === null) {
            return __('Sync Pending');
        }

        if (!$this->access_identity->is_active) {
            return __('Disabled');
        }

        return __('Active');
    }

    /**
     * Legacy computed property for backward compatibility.
     * @deprecated Use deviceSyncStatusText instead
     */
    #[Computed]
    public function enrollmentStatusText(): string
    {
        return $this->deviceSyncStatusText;
    }

    #[Computed]
    public function activeSubscription(): ?\App\Models\MemberSubscription
    {
        return $this->member->subscriptions->where('status', 'active')->first();
    }

    #[Computed]
    public function availablePackages(): Collection
    {
        return MembershipPackage::query()
            ->active()
            ->orderBy('name')
            ->get(['id', 'name', 'price', 'duration_type', 'duration_value']);
    }

    protected function subscriptionRules(): array
    {
        return [
            'membership_package_id' => ['required', Rule::exists('membership_packages', 'id')],
            'start_date' => ['required', 'date', 'after_or_equal:' . now()->subYear()->format('Y-m-d')],
            'auto_renew' => ['boolean'],
            'subscription_notes' => ['nullable', 'string', 'max:1000'],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'payment_method' => ['required', 'string', 'max:100'],
            'reference' => ['nullable', 'string', 'max:120'],
            'paid_at' => ['required', 'date'],
        ];
    }

    public function saveSubscription(): void
    {
        $this->authorize('create', \App\Models\MemberSubscription::class);

        $validated = $this->validate($this->subscriptionRules());

        $package = MembershipPackage::query()->findOrFail($validated['membership_package_id']);

        $payload = [
            'start_date' => $validated['start_date'],
            'auto_renew' => $validated['auto_renew'],
            'notes' => $validated['subscription_notes'],
            'amount' => $validated['amount'],
            'currency' => $validated['currency'],
            'payment_method' => $validated['payment_method'],
            'reference' => $validated['reference'],
            'paid_at' => $validated['paid_at'],
        ];

        try {
            $service = app(SubscriptionService::class);
            $service->startSubscription($this->member, $package, $payload);

            $this->show_subscription_modal = false;
            $this->member->refresh()->load(['subscriptions.membershipPackage']);

            // Refresh fingerprint status as validity may have been updated
            $this->loadFingerprintStatus();

            session()->flash('success', __('Subscription added successfully.'));
        } catch (RuntimeException $exception) {
            $this->addError('subscription_form', $exception->getMessage());
        } catch (Throwable $throwable) {
            report($throwable);
            $this->addError('subscription_form', __('Something went wrong while saving the subscription. Please try again.'));
        }
    }

    public function render(): View
    {
        return view('livewire.members.show', [
            'member' => $this->member,
        ]);
    }
}
