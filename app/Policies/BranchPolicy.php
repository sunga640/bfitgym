<?php

namespace App\Policies;

use App\Models\Branch;
use App\Models\User;
use App\Services\BranchContext;
use Illuminate\Auth\Access\HandlesAuthorization;

class BranchPolicy
{
    use HandlesAuthorization;

    protected BranchContext $branch_context;

    public function __construct()
    {
        $this->branch_context = app(BranchContext::class);
    }

    /**
     * Perform pre-authorization checks.
     * Super-admin bypasses all checks.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return null;
    }

    /**
     * Determine whether the user can view any branches.
     * HQ roles can see all branches; branch users can only see their own.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view branches');
    }

    /**
     * Determine whether the user can view the branch.
     */
    public function view(User $user, Branch $branch): bool
    {
        if (!$user->hasPermissionTo('view branches')) {
            return false;
        }

        // Users with switch permission (HQ roles) can view any branch
        if ($user->hasPermissionTo('switch branches')) {
            return true;
        }

        // Regular users can only view their own branch
        return $user->branch_id === $branch->id;
    }

    /**
     * Determine whether the user can create branches.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create branches');
    }

    /**
     * Determine whether the user can update the branch.
     */
    public function update(User $user, Branch $branch): bool
    {
        if (!$user->hasPermissionTo('edit branches')) {
            return false;
        }

        // Users with switch permission (HQ roles) can edit any branch
        if ($user->hasPermissionTo('switch branches')) {
            return true;
        }

        // Branch admins can only edit their own branch
        return $user->branch_id === $branch->id;
    }

    /**
     * Determine whether the user can delete the branch.
     */
    public function delete(User $user, Branch $branch): bool
    {
        return $user->hasPermissionTo('delete branches');
    }

    /**
     * Determine whether the user can manage branch status (activate/deactivate).
     */
    public function manageStatus(User $user, Branch $branch): bool
    {
        // Only HQ roles with specific permission can activate/deactivate
        return $user->hasPermissionTo('manage branch status');
    }

    /**
     * Determine whether the user can switch to this branch.
     */
    public function switch(User $user, Branch $branch): bool
    {
        // Must have switch permission
        if (!$user->hasPermissionTo('switch branches')) {
            return false;
        }

        // Branch must be active to switch to it
        return $branch->status === 'active';
    }

    /**
     * Determine whether the user can manage branch settings (advanced).
     */
    public function manage(User $user, Branch $branch): bool
    {
        return $user->hasPermissionTo('manage branches');
    }
}

