<?php

namespace App\Policies;

use App\Models\EventRegistration;
use App\Models\User;

class EventRegistrationPolicy extends BranchScopedPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('manage event registrations') && $this->hasCurrentBranchAccess($user);
    }

    public function view(User $user, EventRegistration $registration): bool
    {
        return $user->hasPermissionTo('manage event registrations') && $this->belongsToUserBranch($user, $registration);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('manage event registrations') && $this->hasCurrentBranchAccess($user);
    }

    public function update(User $user, EventRegistration $registration): bool
    {
        return $user->hasPermissionTo('manage event registrations') && $this->belongsToUserBranch($user, $registration);
    }

    public function delete(User $user, EventRegistration $registration): bool
    {
        return $user->hasPermissionTo('manage event registrations') && $this->belongsToUserBranch($user, $registration);
    }
}

