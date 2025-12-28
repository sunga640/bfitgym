<?php

namespace App\Policies;

use App\Models\MemberSubscription;
use App\Models\User;

class MemberSubscriptionPolicy extends BranchScopedPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view subscriptions') && $this->hasCurrentBranchAccess($user);
    }

    public function view(User $user, MemberSubscription $subscription): bool
    {
        return $user->hasPermissionTo('view subscriptions') && $this->belongsToUserBranch($user, $subscription);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create subscriptions') && $this->hasCurrentBranchAccess($user);
    }

    public function update(User $user, MemberSubscription $subscription): bool
    {
        return $user->hasPermissionTo('edit subscriptions') && $this->belongsToUserBranch($user, $subscription);
    }

    public function delete(User $user, MemberSubscription $subscription): bool
    {
        return $user->hasPermissionTo('delete subscriptions') && $this->belongsToUserBranch($user, $subscription);
    }

    public function renew(User $user, MemberSubscription $subscription): bool
    {
        return $user->hasPermissionTo('renew subscriptions') && $this->belongsToUserBranch($user, $subscription);
    }
}


