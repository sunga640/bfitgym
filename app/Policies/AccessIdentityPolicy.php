<?php

namespace App\Policies;

use App\Models\AccessIdentity;
use App\Models\User;
use App\Services\BranchContext;
use Illuminate\Auth\Access\HandlesAuthorization;

class AccessIdentityPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['view access devices', 'manage access identities']);
    }

    public function view(User $user, AccessIdentity $identity): bool
    {
        return $user->hasAnyPermission(['view access devices', 'manage access identities'])
            && $this->belongsToUserBranch($user, $identity);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('manage access identities');
    }

    public function update(User $user, AccessIdentity $identity): bool
    {
        return $user->hasPermissionTo('manage access identities')
            && $this->belongsToUserBranch($user, $identity);
    }

    public function delete(User $user, AccessIdentity $identity): bool
    {
        return $user->hasPermissionTo('manage access identities')
            && $this->belongsToUserBranch($user, $identity);
    }

    protected function belongsToUserBranch(User $user, AccessIdentity $identity): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        $current_branch_id = app(BranchContext::class)->getCurrentBranchId();

        if ($current_branch_id) {
            return $identity->branch_id === $current_branch_id;
        }

        if ($user->branch_id) {
            return $identity->branch_id === $user->branch_id;
        }

        return false;
    }
}

