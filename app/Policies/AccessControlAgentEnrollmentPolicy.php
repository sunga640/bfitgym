<?php

namespace App\Policies;

use App\Models\AccessControlAgentEnrollment;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AccessControlAgentEnrollmentPolicy extends BranchScopedPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['view access devices', 'manage access devices'])
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

        return $user->hasAnyPermission(['view access devices', 'manage access devices'])
            && $this->belongsToUserBranch($user, $model);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('manage access devices') && $this->hasCurrentBranchAccess($user);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Model $model): bool
    {
        if (!$model instanceof AccessControlAgentEnrollment) {
            return false;
        }

        return $user->hasPermissionTo('manage access devices')
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

        return $user->hasPermissionTo('manage access devices')
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

        return $user->hasPermissionTo('manage access devices')
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

        return $user->hasPermissionTo('manage access devices')
            && $this->belongsToUserBranch($user, $model);
    }
}
