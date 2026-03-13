<?php

namespace App\Policies;

use App\Models\AccessControlAgentEnrollment;
use App\Models\AccessControlDevice;
use App\Models\User;
use App\Support\Integrations\IntegrationPermission;
use Illuminate\Database\Eloquent\Model;

class AccessControlAgentEnrollmentPolicy extends BranchScopedPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return (
            IntegrationPermission::canView($user, AccessControlDevice::INTEGRATION_HIKVISION)
            || IntegrationPermission::canView($user, AccessControlDevice::INTEGRATION_ZKTECO)
        )
            && $this->hasCurrentBranchAccess($user);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Model $model): bool
    {
        if (!$model instanceof AccessControlAgentEnrollment) {
            return false;
        }

        return IntegrationPermission::canView($user, $model->integration_type)
            && $this->belongsToUserBranch($user, $model);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return (
            IntegrationPermission::canManage($user, AccessControlDevice::INTEGRATION_HIKVISION)
            || IntegrationPermission::canManage($user, AccessControlDevice::INTEGRATION_ZKTECO)
        ) && $this->hasCurrentBranchAccess($user);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Model $model): bool
    {
        if (!$model instanceof AccessControlAgentEnrollment) {
            return false;
        }

        return IntegrationPermission::canManage($user, $model->integration_type)
            && $this->belongsToUserBranch($user, $model);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Model $model): bool
    {
        if (!$model instanceof AccessControlAgentEnrollment) {
            return false;
        }

        return IntegrationPermission::canManage($user, $model->integration_type)
            && $this->belongsToUserBranch($user, $model);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Model $model): bool
    {
        if (!$model instanceof AccessControlAgentEnrollment) {
            return false;
        }

        return IntegrationPermission::canManage($user, $model->integration_type)
            && $this->belongsToUserBranch($user, $model);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Model $model): bool
    {
        if (!$model instanceof AccessControlAgentEnrollment) {
            return false;
        }

        return IntegrationPermission::canManage($user, $model->integration_type)
            && $this->belongsToUserBranch($user, $model);
    }
}
