<?php

namespace App\Policies;

use App\Models\AccessControlDevice;
use App\Models\User;
use App\Services\BranchContext;
use App\Support\Integrations\IntegrationPermission;
use Illuminate\Auth\Access\HandlesAuthorization;

class AccessControlDevicePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return IntegrationPermission::canView($user, AccessControlDevice::INTEGRATION_HIKVISION)
            || IntegrationPermission::canView($user, AccessControlDevice::INTEGRATION_ZKTECO);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, AccessControlDevice $device): bool
    {
        if (!IntegrationPermission::canView($user, $device->integration_type)) {
            return false;
        }

        return $this->belongsToUserBranch($user, $device);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return IntegrationPermission::canManage($user, AccessControlDevice::INTEGRATION_HIKVISION)
            || IntegrationPermission::canManage($user, AccessControlDevice::INTEGRATION_ZKTECO);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, AccessControlDevice $device): bool
    {
        if (!IntegrationPermission::canManage($user, $device->integration_type)) {
            return false;
        }

        return $this->belongsToUserBranch($user, $device);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, AccessControlDevice $device): bool
    {
        if (!IntegrationPermission::canManage($user, $device->integration_type)) {
            return false;
        }

        return $this->belongsToUserBranch($user, $device);
    }

    /**
     * Determine whether the user can test connection to the device.
     */
    public function testConnection(User $user, AccessControlDevice $device): bool
    {
        if (!IntegrationPermission::canManage($user, $device->integration_type)) {
            return false;
        }

        return $this->belongsToUserBranch($user, $device);
    }

    /**
     * Determine whether the user can sync the device.
     */
    public function sync(User $user, AccessControlDevice $device): bool
    {
        if (!IntegrationPermission::canManage($user, $device->integration_type)) {
            return false;
        }

        return $this->belongsToUserBranch($user, $device);
    }

    /**
     * Check if device belongs to user's accessible branches.
     */
    protected function belongsToUserBranch(User $user, AccessControlDevice $device): bool
    {
        // Super admin can access all
        if ($user->hasRole('super-admin')) {
            return true;
        }

        $current_branch_id = app(BranchContext::class)->getCurrentBranchId();

        // If user has a specific branch, check it matches
        if ($current_branch_id) {
            return $device->branch_id === $current_branch_id;
        }

        // User has a single branch assigned
        if ($user->branch_id) {
            return $device->branch_id === $user->branch_id;
        }

        return false;
    }
}
