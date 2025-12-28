<?php

namespace App\Policies;

use App\Models\Member;
use App\Models\User;

class MemberPolicy extends BranchScopedPolicy
{
    /**
     * Determine whether the user can view any members.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view members') && $this->hasCurrentBranchAccess($user);
    }

    /**
     * Determine whether the user can view the member.
     */
    public function view(User $user, Member $member): bool
    {
        return $user->hasPermissionTo('view members') && $this->belongsToUserBranch($user, $member);
    }

    /**
     * Determine whether the user can create members.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create members') && $this->hasCurrentBranchAccess($user);
    }

    /**
     * Determine whether the user can update the member.
     */
    public function update(User $user, Member $member): bool
    {
        return $user->hasPermissionTo('edit members') && $this->belongsToUserBranch($user, $member);
    }

    /**
     * Determine whether the user can delete the member.
     */
    public function delete(User $user, Member $member): bool
    {
        return $user->hasPermissionTo('delete members') && $this->belongsToUserBranch($user, $member);
    }

    /**
     * Determine whether the user can export members.
     */
    public function export(User $user): bool
    {
        return $user->hasPermissionTo('export members') && $this->hasCurrentBranchAccess($user);
    }

    /**
     * Determine whether the user can restore the member.
     */
    public function restore(User $user, Member $member): bool
    {
        return $user->hasPermissionTo('edit members') && $this->belongsToUserBranch($user, $member);
    }

    /**
     * Determine whether the user can permanently delete the member.
     */
    public function forceDelete(User $user, Member $member): bool
    {
        return $user->hasPermissionTo('delete members') && $this->belongsToUserBranch($user, $member);
    }
}

