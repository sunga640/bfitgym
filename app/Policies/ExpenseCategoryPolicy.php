<?php

namespace App\Policies;

use App\Models\ExpenseCategory;
use App\Models\User;

class ExpenseCategoryPolicy extends BranchScopedPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view expenses') && $this->hasCurrentBranchAccess($user);
    }

    public function view(User $user, ExpenseCategory $category): bool
    {
        return $user->hasPermissionTo('view expenses') && $this->canViewCategory($user, $category);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create expenses') && $this->hasCurrentBranchAccess($user);
    }

    public function update(User $user, ExpenseCategory $category): bool
    {
        return $user->hasPermissionTo('edit expenses') && $this->canManageCategory($user, $category);
    }

    public function delete(User $user, ExpenseCategory $category): bool
    {
        return $user->hasPermissionTo('delete expenses') && $this->canManageCategory($user, $category);
    }

    protected function canViewCategory(User $user, ExpenseCategory $category): bool
    {
        if ($category->branch_id === null) {
            return $this->hasCurrentBranchAccess($user);
        }

        return $this->belongsToUserBranch($user, $category);
    }

    protected function canManageCategory(User $user, ExpenseCategory $category): bool
    {
        if ($category->branch_id === null) {
            return $user->hasRole('super-admin');
        }

        return $this->belongsToUserBranch($user, $category);
    }
}

