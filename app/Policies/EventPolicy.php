<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;

class EventPolicy extends BranchScopedPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view events') && $this->hasCurrentBranchAccess($user);
    }

    public function view(User $user, Event $event): bool
    {
        return $user->hasPermissionTo('view events') && $this->belongsToUserBranch($user, $event);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create events') && $this->hasCurrentBranchAccess($user);
    }

    public function update(User $user, Event $event): bool
    {
        return $user->hasPermissionTo('edit events') && $this->belongsToUserBranch($user, $event);
    }

    public function delete(User $user, Event $event): bool
    {
        return $user->hasPermissionTo('delete events') && $this->belongsToUserBranch($user, $event);
    }
}

