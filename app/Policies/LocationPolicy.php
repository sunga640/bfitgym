<?php

namespace App\Policies;

use App\Models\Location;
use App\Models\User;

class LocationPolicy extends BranchScopedPolicy
{
    /**
     * Determine whether the user can view any locations.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view equipment') && $this->hasCurrentBranchAccess($user);
    }

    /**
     * Determine whether the user can view the location.
     */
    public function view(User $user, Location $location): bool
    {
        return $user->hasPermissionTo('view equipment') && $this->belongsToUserBranch($user, $location);
    }

    /**
     * Determine whether the user can create locations.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('manage equipment') && $this->hasCurrentBranchAccess($user);
    }

    /**
     * Determine whether the user can update the location.
     */
    public function update(User $user, Location $location): bool
    {
        return $user->hasPermissionTo('manage equipment') && $this->belongsToUserBranch($user, $location);
    }

    /**
     * Determine whether the user can delete the location.
     */
    public function delete(User $user, Location $location): bool
    {
        return $user->hasPermissionTo('manage equipment') && $this->belongsToUserBranch($user, $location);
    }

    /**
     * Determine whether the user can restore the location.
     */
    public function restore(User $user, Location $location): bool
    {
        return $user->hasPermissionTo('manage equipment') && $this->belongsToUserBranch($user, $location);
    }

    /**
     * Determine whether the user can permanently delete the location.
     */
    public function forceDelete(User $user, Location $location): bool
    {
        return $user->hasPermissionTo('manage equipment') && $this->belongsToUserBranch($user, $location);
    }
}

