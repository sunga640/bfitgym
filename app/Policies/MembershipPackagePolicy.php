<?php

namespace App\Policies;

use App\Models\MembershipPackage;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class MembershipPackagePolicy extends BranchScopedPolicy
{
    /**
     * Determine whether the user can view any membership packages.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view membership-packages') && $this->hasCurrentBranchAccess($user);
    }

    /**
     * Determine whether the user can view the membership package.
     */
    public function view(User $user, Model $model): bool
    {
        return $user->hasPermissionTo('view membership-packages') && $this->belongsToUserBranch($user, $model);
    }

    /**
     * Determine whether the user can create membership packages.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create membership-packages') && $this->hasCurrentBranchAccess($user);
    }

    /**
     * Determine whether the user can update the membership package.
     */
    public function update(User $user, Model $model): bool
    {
        return $user->hasPermissionTo('edit membership-packages') && $this->belongsToUserBranch($user, $model);
    }

    /**
     * Determine whether the user can delete the membership package.
     */
    public function delete(User $user, Model $model): bool
    {
        return $user->hasPermissionTo('delete membership-packages') && $this->belongsToUserBranch($user, $model);
    }

    /**
     * Determine whether the user can restore the membership package.
     */
    public function restore(User $user, Model $model): bool
    {
        return $user->hasPermissionTo('edit membership-packages') && $this->belongsToUserBranch($user, $model);
    }

    /**
     * Determine whether the user can permanently delete the membership package.
     */
    public function forceDelete(User $user, Model $model): bool
    {
        return $user->hasPermissionTo('delete membership-packages') && $this->belongsToUserBranch($user, $model);
    }
}
