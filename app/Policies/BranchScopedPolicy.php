<?php

namespace App\Policies;

use App\Models\User;
use App\Services\BranchContext;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Database\Eloquent\Model;

abstract class BranchScopedPolicy
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
     * Check if user can access the model based on branch.
     */
    protected function belongsToUserBranch(User $user, Model $model): bool
    {
        // If model doesn't have branch_id, allow access
        if (!isset($model->branch_id)) {
            return true;
        }

        // Check if user has access to the model's branch
        return $this->branch_context->hasAccessToBranch($model->branch_id, $user);
    }

    /**
     * Check if user can access current branch context.
     */
    protected function hasCurrentBranchAccess(User $user): bool
    {
        $current_branch_id = $this->branch_context->getCurrentBranchId();

        if ($current_branch_id === null) {
            // No branch context - allow if user has switch permission or is super-admin
            return $user->hasRole('super-admin') || $user->hasPermissionTo('switch branches');
        }

        return $this->branch_context->hasAccessToBranch($current_branch_id, $user);
    }
}

