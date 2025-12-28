<?php

namespace App\Policies;

use App\Models\ClassType;
use App\Models\User;

class ClassTypePolicy extends BranchScopedPolicy
{
    /**
     * Determine whether the user can view any class types.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view classes') && $this->hasCurrentBranchAccess($user);
    }

    /**
     * Determine whether the user can view the class type.
     */
    public function view(User $user, ClassType $class_type): bool
    {
        return $user->hasPermissionTo('view classes') && $this->belongsToUserBranch($user, $class_type);
    }

    /**
     * Determine whether the user can create class types.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create classes') && $this->hasCurrentBranchAccess($user);
    }

    /**
     * Determine whether the user can update the class type.
     */
    public function update(User $user, ClassType $class_type): bool
    {
        return $user->hasPermissionTo('edit classes') && $this->belongsToUserBranch($user, $class_type);
    }

    /**
     * Determine whether the user can delete the class type.
     */
    public function delete(User $user, ClassType $class_type): bool
    {
        return $user->hasPermissionTo('delete classes') && $this->belongsToUserBranch($user, $class_type);
    }

    /**
     * Determine whether the user can restore the class type.
     */
    public function restore(User $user, ClassType $class_type): bool
    {
        return $user->hasPermissionTo('edit classes') && $this->belongsToUserBranch($user, $class_type);
    }

    /**
     * Determine whether the user can permanently delete the class type.
     */
    public function forceDelete(User $user, ClassType $class_type): bool
    {
        return $user->hasPermissionTo('delete classes') && $this->belongsToUserBranch($user, $class_type);
    }
}

