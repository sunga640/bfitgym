<?php

namespace App\Policies;

use App\Models\CvSecurityEvent;
use App\Models\User;

class CvSecurityEventPolicy extends BranchScopedPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['view zkteco', 'manage zkteco', 'manage zkteco settings']);
    }

    public function view(User $user, CvSecurityEvent $event): bool
    {
        return $this->viewAny($user) && $this->belongsToUserBranch($user, $event);
    }
}

