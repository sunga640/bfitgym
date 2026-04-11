<?php

namespace App\Policies;

use App\Models\Expense;
use App\Models\User;

class ExpensePolicy extends BranchScopedPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view expenses') && $this->hasCurrentBranchAccess($user);
    }

    public function view(User $user, Expense $expense): bool
    {
        return $user->hasPermissionTo('view expenses') && $this->belongsToUserBranch($user, $expense);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create expenses') && $this->hasCurrentBranchAccess($user);
    }

    public function update(User $user, Expense $expense): bool
    {
        return $user->hasPermissionTo('edit expenses') && $this->belongsToUserBranch($user, $expense);
    }

    public function delete(User $user, Expense $expense): bool
    {
        return $user->hasPermissionTo('delete expenses') && $this->belongsToUserBranch($user, $expense);
    }

    public function approve(User $user, Expense $expense): bool
    {
        return $user->hasPermissionTo('approve expenses') && $this->belongsToUserBranch($user, $expense);
    }
}

