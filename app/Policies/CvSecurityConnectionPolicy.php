<?php

namespace App\Policies;

use App\Models\CvSecurityConnection;
use App\Models\User;

class CvSecurityConnectionPolicy extends BranchScopedPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['view zkteco', 'manage zkteco', 'manage zkteco settings']);
    }

    public function view(User $user, CvSecurityConnection $connection): bool
    {
        return $this->viewAny($user) && $this->belongsToUserBranch($user, $connection);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyPermission(['manage zkteco', 'manage zkteco settings']);
    }

    public function update(User $user, CvSecurityConnection $connection): bool
    {
        return $this->create($user) && $this->belongsToUserBranch($user, $connection);
    }

    public function delete(User $user, CvSecurityConnection $connection): bool
    {
        return $this->update($user, $connection);
    }

    public function manage(User $user, CvSecurityConnection $connection): bool
    {
        return $this->update($user, $connection);
    }
}

