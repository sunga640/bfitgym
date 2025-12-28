<?php

namespace App\Policies;

use App\Models\EquipmentAllocation;
use App\Models\User;

class EquipmentAllocationPolicy extends BranchScopedPolicy
{
    /**
     * Determine whether the user can view any equipment allocations.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view equipment allocations') && $this->hasCurrentBranchAccess($user);
    }

    /**
     * Determine whether the user can view the equipment allocation.
     */
    public function view(User $user, EquipmentAllocation $allocation): bool
    {
        return $user->hasPermissionTo('view equipment allocations') && $this->belongsToUserBranch($user, $allocation);
    }

    /**
     * Determine whether the user can create equipment allocations.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('manage equipment allocations') && $this->hasCurrentBranchAccess($user);
    }

    /**
     * Determine whether the user can update the equipment allocation.
     */
    public function update(User $user, EquipmentAllocation $allocation): bool
    {
        return $user->hasPermissionTo('manage equipment allocations') && $this->belongsToUserBranch($user, $allocation);
    }

    /**
     * Determine whether the user can delete the equipment allocation.
     */
    public function delete(User $user, EquipmentAllocation $allocation): bool
    {
        return $user->hasPermissionTo('manage equipment allocations') && $this->belongsToUserBranch($user, $allocation);
    }
}

