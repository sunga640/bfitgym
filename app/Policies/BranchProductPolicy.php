<?php

namespace App\Policies;

use App\Models\BranchProduct;
use App\Models\User;
use App\Services\BranchContext;
use Illuminate\Auth\Access\HandlesAuthorization;

class BranchProductPolicy
{
    use HandlesAuthorization;

    protected BranchContext $branch_context;

    public function __construct(BranchContext $branch_context)
    {
        $this->branch_context = $branch_context;
    }

    /**
     * Determine whether the user can view any branch products.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view inventory') || $user->hasRole('super-admin');
    }

    /**
     * Determine whether the user can view the branch product.
     */
    public function view(User $user, BranchProduct $branch_product): bool
    {
        if (!$user->hasPermissionTo('view inventory') && !$user->hasRole('super-admin')) {
            return false;
        }

        return $this->branch_context->belongsToCurrentBranch($branch_product);
    }

    /**
     * Determine whether the user can create branch products.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('manage inventory') || $user->hasRole('super-admin');
    }

    /**
     * Determine whether the user can update the branch product.
     */
    public function update(User $user, BranchProduct $branch_product): bool
    {
        if (!$user->hasPermissionTo('manage inventory') && !$user->hasRole('super-admin')) {
            return false;
        }

        return $this->branch_context->belongsToCurrentBranch($branch_product);
    }

    /**
     * Determine whether the user can delete the branch product.
     */
    public function delete(User $user, BranchProduct $branch_product): bool
    {
        if (!$user->hasPermissionTo('manage inventory') && !$user->hasRole('super-admin')) {
            return false;
        }

        return $this->branch_context->belongsToCurrentBranch($branch_product);
    }
}

