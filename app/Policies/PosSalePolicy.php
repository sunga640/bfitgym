<?php

namespace App\Policies;

use App\Models\PosSale;
use App\Models\User;
use App\Services\BranchContext;
use Illuminate\Auth\Access\HandlesAuthorization;

class PosSalePolicy
{
    use HandlesAuthorization;

    protected BranchContext $branch_context;

    public function __construct(BranchContext $branch_context)
    {
        $this->branch_context = $branch_context;
    }

    /**
     * Determine whether the user can view any POS sales.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view pos') || $user->hasRole('super-admin');
    }

    /**
     * Determine whether the user can view the POS sale.
     */
    public function view(User $user, PosSale $pos_sale): bool
    {
        if (!$user->hasPermissionTo('view pos') && !$user->hasRole('super-admin')) {
            return false;
        }

        return $this->branch_context->belongsToCurrentBranch($pos_sale);
    }

    /**
     * Determine whether the user can create POS sales.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create pos sales') || $user->hasRole('super-admin');
    }

    /**
     * Determine whether the user can refund the POS sale.
     */
    public function refund(User $user, PosSale $pos_sale): bool
    {
        if (!$user->hasPermissionTo('refund pos sales') && !$user->hasRole('super-admin')) {
            return false;
        }

        return $this->branch_context->belongsToCurrentBranch($pos_sale);
    }
}

