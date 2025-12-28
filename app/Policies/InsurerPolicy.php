<?php

namespace App\Policies;

use App\Models\Insurer;
use App\Models\User;

class InsurerPolicy extends BranchScopedPolicy
{
    /**
     * Determine whether the user can view any insurers.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view insurers');
    }

    /**
     * Determine whether the user can view the insurer.
     */
    public function view(User $user, Insurer $insurer): bool
    {
        return $user->hasPermissionTo('view insurers');
    }

    /**
     * Determine whether the user can create insurers.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('manage insurers');
    }

    /**
     * Determine whether the user can update the insurer.
     */
    public function update(User $user, Insurer $insurer): bool
    {
        return $user->hasPermissionTo('manage insurers');
    }

    /**
     * Determine whether the user can delete the insurer.
     */
    public function delete(User $user, Insurer $insurer): bool
    {
        return $user->hasPermissionTo('manage insurers');
    }

    /**
     * Determine whether the user can restore the insurer.
     */
    public function restore(User $user, Insurer $insurer): bool
    {
        return $user->hasPermissionTo('manage insurers');
    }

    /**
     * Determine whether the user can permanently delete the insurer.
     */
    public function forceDelete(User $user, Insurer $insurer): bool
    {
        return $user->hasPermissionTo('manage insurers');
    }
}

