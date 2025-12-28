<?php

namespace App\Policies;

use App\Models\PurchaseOrder;
use App\Models\User;
use App\Services\BranchContext;
use Illuminate\Auth\Access\HandlesAuthorization;

class PurchaseOrderPolicy
{
    use HandlesAuthorization;

    protected BranchContext $branch_context;

    public function __construct(BranchContext $branch_context)
    {
        $this->branch_context = $branch_context;
    }

    /**
     * Determine whether the user can view any purchase orders.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view purchase orders') || $user->hasRole('super-admin');
    }

    /**
     * Determine whether the user can view the purchase order.
     */
    public function view(User $user, PurchaseOrder $purchase_order): bool
    {
        if (!$user->hasPermissionTo('view purchase orders') && !$user->hasRole('super-admin')) {
            return false;
        }

        return $this->branch_context->belongsToCurrentBranch($purchase_order);
    }

    /**
     * Determine whether the user can create purchase orders.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create purchase orders') || $user->hasRole('super-admin');
    }

    /**
     * Determine whether the user can update the purchase order.
     */
    public function update(User $user, PurchaseOrder $purchase_order): bool
    {
        if (!$user->hasPermissionTo('edit purchase orders') && !$user->hasRole('super-admin')) {
            return false;
        }

        return $this->branch_context->belongsToCurrentBranch($purchase_order);
    }

    /**
     * Determine whether the user can delete the purchase order.
     */
    public function delete(User $user, PurchaseOrder $purchase_order): bool
    {
        if (!$user->hasPermissionTo('delete purchase orders') && !$user->hasRole('super-admin')) {
            return false;
        }

        // Cannot delete received orders
        if ($purchase_order->status === 'received') {
            return false;
        }

        return $this->branch_context->belongsToCurrentBranch($purchase_order);
    }

    /**
     * Determine whether the user can receive the purchase order.
     */
    public function receive(User $user, PurchaseOrder $purchase_order): bool
    {
        if (!$user->hasPermissionTo('receive purchase orders') && !$user->hasRole('super-admin')) {
            return false;
        }

        return $this->branch_context->belongsToCurrentBranch($purchase_order);
    }
}

