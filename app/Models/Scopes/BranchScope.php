<?php

namespace App\Models\Scopes;

use App\Services\BranchContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class BranchScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $user = Auth::user();

        // Skip scope if no authenticated user (e.g., console commands, queues)
        if (!$user) {
            return;
        }

        // Skip scope for super-admin when no branch is explicitly set in session
        if ($user->hasRole('super-admin') && !session()->has('current_branch_id')) {
            return;
        }

        $branch_context = app(BranchContext::class);
        $current_branch_id = $branch_context->getCurrentBranchId();

        if ($current_branch_id !== null) {
            $builder->where($model->getTable() . '.branch_id', $current_branch_id);
        }
    }
}

