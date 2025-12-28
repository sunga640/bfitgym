<?php

namespace App\Policies;

use App\Models\Equipment;
use App\Models\User;

class EquipmentPolicy extends BranchScopedPolicy
{
    /**
     * Determine whether the user can view any equipment.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view equipment');
    }

    /**
     * Determine whether the user can view the equipment.
     */
    public function view(User $user, Equipment $equipment): bool
    {
        return $user->hasPermissionTo('view equipment');
    }

    /**
     * Determine whether the user can create equipment.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('manage equipment');
    }

    /**
     * Determine whether the user can update the equipment.
     */
    public function update(User $user, Equipment $equipment): bool
    {
        return $user->hasPermissionTo('manage equipment');
    }

    /**
     * Determine whether the user can delete the equipment.
     */
    public function delete(User $user, Equipment $equipment): bool
    {
        return $user->hasPermissionTo('manage equipment');
    }

    /**
     * Determine whether the user can restore the equipment.
     */
    public function restore(User $user, Equipment $equipment): bool
    {
        return $user->hasPermissionTo('manage equipment');
    }

    /**
     * Determine whether the user can permanently delete the equipment.
     */
    public function forceDelete(User $user, Equipment $equipment): bool
    {
        return $user->hasPermissionTo('manage equipment');
    }
}

