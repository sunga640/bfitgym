<?php

namespace App\Livewire\Users;

use App\Models\AccessControlDeviceCommand;
use App\Models\AccessIdentity;
use App\Models\User;
use App\Services\AccessControl\AccessControlCommandService;
use App\Services\AccessControl\AccessControlService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Show extends Component
{
    public User $user;
    public bool $has_fingerprint_access = false;
    public bool $has_fingerprint_registered = false;
    public ?AccessIdentity $access_identity = null;

    public function mount(User $user): void
    {
        $this->user = $user->load(['branch', 'roles']);
        $this->authorize('view', $user);

        $this->loadFingerprintStatus();
    }

    protected function loadFingerprintStatus(): void
    {
        $service = app(AccessControlService::class);
        $this->access_identity = $service->getStaffIdentity($this->user);

        // A user is "registered" if identity exists AND was synced to device
        $this->has_fingerprint_registered = $this->access_identity !== null
            && $this->access_identity->device_synced_at !== null;

        // Access is active only if synced AND is_active
        $this->has_fingerprint_access = $this->has_fingerprint_registered
            && ($this->access_identity?->is_active ?? false);
    }

    /**
     * Sync staff user to access control device.
     * Creates AccessIdentity (active) and enqueues person_upsert command for local agent.
     * Staff are set as "long term" users (no end date).
     */
    public function syncUserToDevice(): void
    {
        $this->authorize('update', $this->user);

        // Staff must have a branch
        if (!$this->user->branch_id) {
            session()->flash('error', __('Staff must be assigned to a branch before syncing to device.'));
            return;
        }

        // Check for existing identity that's already synced
        $existing_identity = $this->access_identity;
        if ($existing_identity && $existing_identity->device_synced_at !== null) {
            session()->flash('error', __('Staff is already synced to device.'));
            return;
        }

        $access_logger = app(\App\Support\AccessLogger::class);

        try {
            $device_user_id = $this->getStaffDeviceUserId();
            if ($device_user_id === '') {
                session()->flash('error', __('Cannot generate device user ID for staff.'));
                return;
            }

            $target = app(AccessControlCommandService::class)->resolveIntegrationAndProvider(
                branch_id: $this->user->branch_id,
                subject_type: AccessIdentity::SUBJECT_STAFF,
                subject_id: $this->user->id,
            );

            $valid_from = now();
            // Staff have long-term access - set to far future date (10 years)
            $valid_until = now()->addYears(10);

            // Force delete any existing AccessIdentity that could conflict (including soft-deleted)
            AccessIdentity::withTrashed()
                ->where('branch_id', $this->user->branch_id)
                ->where('integration_type', $target['integration_type'])
                ->where(function ($query) use ($device_user_id) {
                    $query->where('device_user_id', $device_user_id)
                        ->orWhere(function ($q) {
                            $q->where('subject_type', AccessIdentity::SUBJECT_STAFF)
                                ->where('subject_id', $this->user->id);
                        });
                })
                ->forceDelete();

            // Create fresh AccessIdentity (active immediately - fingerprint added later via device UI)
            $access_identity = AccessIdentity::create([
                'branch_id' => $this->user->branch_id,
                'integration_type' => $target['integration_type'],
                'provider' => $target['provider'],
                'device_user_id' => $device_user_id,
                'subject_type' => AccessIdentity::SUBJECT_STAFF,
                'subject_id' => $this->user->id,
                'is_active' => true,
                'fingerprint_enrolled_at' => null,
                'valid_from' => $valid_from,
                'valid_until' => $valid_until,
                'last_sync_error' => null,
            ]);

            // Enqueue user sync command
            $result = app(AccessControlCommandService::class)->enqueueStaffUserSync(
                user: $this->user,
                access_identity: $access_identity,
                valid_from: $valid_from,
                valid_until: $valid_until,
            );

            $access_logger->info('staff_sync_to_device_started', [
                'user_id' => $this->user->id,
                'user_name' => $this->user->name,
                'access_identity_id' => $access_identity->id,
                'command_id' => $result['command']->id,
            ]);

            $this->loadFingerprintStatus();

            session()->flash('success', __('User sync initiated. The staff member will be added to the device. Add fingerprint via device web dashboard.'));
        } catch (\App\Exceptions\AccessControl\AccessControlActionException $e) {
            $access_logger->error('staff_sync_to_device_failed', [
                'user_id' => $this->user->id,
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', $e->getMessage());
        } catch (\Throwable $e) {
            report($e);
            $access_logger->error('staff_sync_to_device_failed', [
                'user_id' => $this->user->id,
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', __('Failed to sync user to device. Please try again.'));
        }
    }

    /**
     * Enable a previously disabled access.
     * Restores validity to long-term (10 years).
     */
    public function enableFingerprint(): void
    {
        $this->authorize('update', $this->user);

        $access_logger = app(\App\Support\AccessLogger::class);

        if (!$this->access_identity) {
            session()->flash('error', __('Staff does not have device access registered.'));
            return;
        }

        // Must have been synced to device to enable
        if ($this->access_identity->device_synced_at === null) {
            session()->flash('error', __('User has not been synced to device. Please sync first.'));
            return;
        }

        try {
            // Staff have long-term access
            $valid_until = now()->addYears(10);

            // Update local state
            $this->access_identity->update([
                'is_active' => true,
                'valid_until' => $valid_until,
                'disabled_at' => null,
                'last_sync_error' => null,
            ]);

            // Enqueue command to update device
            $command_uuids = app(AccessControlCommandService::class)
                ->enqueueEnableFingerprintForStaff(
                    $this->user,
                    auth()->user(),
                    $this->access_identity->integration_type,
                    $this->access_identity->provider
                );

            $access_logger->info('staff_device_access_enabled', [
                'user_id' => $this->user->id,
                'user_name' => $this->user->name,
                'access_identity_id' => $this->access_identity->id,
                'valid_until' => $valid_until->toDateString(),
                'actor_user_id' => auth()->id(),
                'command_count' => count($command_uuids),
            ]);

            session()->flash('success', __('Device access enabled. Staff has long-term access.'));
            $this->loadFingerprintStatus();
        } catch (\App\Exceptions\AccessControl\AccessControlActionException $e) {
            $access_logger->error('staff_device_access_enable_failed', [
                'user_id' => $this->user->id,
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', $e->getMessage());
        } catch (\Throwable $e) {
            report($e);
            $access_logger->error('staff_device_access_enable_failed', [
                'user_id' => $this->user->id,
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', __('Failed to enable device access. Please try again.'));
        }
    }

    /**
     * Disable device access by setting validity to yesterday.
     */
    public function disableFingerprint(): void
    {
        $this->authorize('update', $this->user);

        $access_logger = app(\App\Support\AccessLogger::class);

        if (!$this->access_identity) {
            session()->flash('error', __('Staff does not have device access registered.'));
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
                ->enqueueDisableFingerprintForStaff(
                    $this->user,
                    auth()->user(),
                    $this->access_identity->integration_type,
                    $this->access_identity->provider
                );

            $access_logger->info('staff_device_access_disabled', [
                'user_id' => $this->user->id,
                'user_name' => $this->user->name,
                'access_identity_id' => $this->access_identity->id,
                'original_valid_until' => $original_valid_until?->toDateString(),
                'actor_user_id' => auth()->id(),
                'command_count' => count($command_uuids),
            ]);

            session()->flash('success', __('Device access disabled.'));
            $this->loadFingerprintStatus();
        } catch (\App\Exceptions\AccessControl\AccessControlActionException $e) {
            $access_logger->error('staff_device_access_disable_failed', [
                'user_id' => $this->user->id,
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', $e->getMessage());
        } catch (\Throwable $e) {
            report($e);
            $access_logger->error('staff_device_access_disable_failed', [
                'user_id' => $this->user->id,
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', __('Failed to disable device access. Please try again.'));
        }
    }

    /**
     * Permanently remove device access.
     */
    public function removeFingerprint(): void
    {
        $this->authorize('update', $this->user);

        $service = app(AccessControlService::class);
        $result = $service->removeStaffFingerprint($this->user);

        if ($result['success']) {
            session()->flash('success', $result['message']);
            $this->loadFingerprintStatus();
        } else {
            session()->flash('error', $result['message']);
        }
    }

    /**
     * Get the stable device_user_id for this staff member.
     * Format: STAFF-{users.id}
     */
    protected function getStaffDeviceUserId(): string
    {
        return 'STAFF-' . $this->user->id;
    }

    #[Computed]
    public function last_access_set_validity_command(): ?AccessControlDeviceCommand
    {
        return AccessControlDeviceCommand::query()
            ->where('branch_id', $this->user->branch_id)
            ->where('subject_type', 'staff')
            ->where('subject_id', $this->user->id)
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
        if (!$cmd) {
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
        if (!$cmd) {
            return false;
        }

        if (!$cmd->created_at || $cmd->created_at->lt(now()->subMinutes(10))) {
            return false;
        }

        if (!in_array($cmd->status, ['pending', 'claimed', 'processing', 'done'], true)) {
            return false;
        }

        return $this->last_command_action === 'disable';
    }

    #[Computed]
    public function last_command_failed(): bool
    {
        return $this->last_command_status === 'failed';
    }

    #[Computed]
    public function canSyncToDevice(): bool
    {
        // Staff must have a branch and not already synced to device
        return $this->user->branch_id !== null && !$this->has_fingerprint_registered;
    }

    #[Computed]
    public function hasPendingSync(): bool
    {
        return $this->access_identity !== null
            && $this->access_identity->device_synced_at === null;
    }

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

    public function render(): View
    {
        return view('livewire.users.show', [
            'user' => $this->user,
        ]);
    }
}
