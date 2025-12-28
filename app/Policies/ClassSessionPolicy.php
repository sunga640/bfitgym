<?php

namespace App\Policies;

use App\Models\ClassSession;
use App\Models\User;

class ClassSessionPolicy extends BranchScopedPolicy
{
    /**
     * Determine whether the user can view any class sessions.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view classes') && $this->hasCurrentBranchAccess($user);
    }

    /**
     * Determine whether the user can view the class session.
     */
    public function view(User $user, ClassSession $class_session): bool
    {
        return $user->hasPermissionTo('view classes') && $this->belongsToUserBranch($user, $class_session);
    }

    /**
     * Determine whether the user can create class sessions.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('manage class sessions') && $this->hasCurrentBranchAccess($user);
    }

    /**
     * Determine whether the user can update the class session.
     */
    public function update(User $user, ClassSession $class_session): bool
    {
        return $user->hasPermissionTo('manage class sessions') && $this->belongsToUserBranch($user, $class_session);
    }

    /**
     * Determine whether the user can delete the class session.
     */
    public function delete(User $user, ClassSession $class_session): bool
    {
        return $user->hasPermissionTo('manage class sessions') && $this->belongsToUserBranch($user, $class_session);
    }

    /**
     * Determine whether the user can restore the class session.
     */
    public function restore(User $user, ClassSession $class_session): bool
    {
        return $user->hasPermissionTo('manage class sessions') && $this->belongsToUserBranch($user, $class_session);
    }

    /**
     * Determine whether the user can permanently delete the class session.
     */
    public function forceDelete(User $user, ClassSession $class_session): bool
    {
        return $user->hasPermissionTo('manage class sessions') && $this->belongsToUserBranch($user, $class_session);
    }
}

