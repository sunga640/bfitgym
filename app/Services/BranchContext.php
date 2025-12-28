<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class BranchContext
{
    protected const SESSION_KEY = 'current_branch_id';

    /**
     * Get the current branch ID from session or user's assigned branch.
     */
    public function getCurrentBranchId(): ?int
    {
        // First check session (for users who can switch branches)
        if (Session::has(self::SESSION_KEY)) {
            return Session::get(self::SESSION_KEY);
        }

        // Fall back to authenticated user's branch
        $user = Auth::user();
        if ($user instanceof User) {
            return $user->branch_id;
        }

        return null;
    }

    /**
     * Initialize branch context on login.
     * Sets the current branch if not already set.
     */
    public function initializeOnLogin(?User $user = null): void
    {
        $user = $user ?? Auth::user();

        if (!$user instanceof User) {
            return;
        }

        // If session already has a branch, nothing to do
        if (Session::has(self::SESSION_KEY)) {
            return;
        }

        // If user has a branch_id, use it
        if ($user->branch_id) {
            Session::put(self::SESSION_KEY, $user->branch_id);
            return;
        }

        // If user has HQ role (can switch branches), set first active branch
        if ($this->canSwitchBranches($user)) {
            $first_branch = Branch::active()->first();
            if ($first_branch) {
                Session::put(self::SESSION_KEY, $first_branch->id);
            }
        }
    }

    /**
     * Check if current branch context is set.
     */
    public function hasBranchContext(): bool
    {
        return $this->getCurrentBranchId() !== null;
    }

    /**
     * Get the current branch model.
     */
    public function getCurrentBranch(): ?Branch
    {
        $branch_id = $this->getCurrentBranchId();

        if ($branch_id) {
            return Branch::find($branch_id);
        }

        return null;
    }

    /**
     * Set the current branch ID in session.
     * Only allowed for users with 'switch branches' permission.
     */
    public function setCurrentBranch(int $branch_id): bool
    {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        // Check if user can switch branches (super-admin or has permission)
        if (!$this->canSwitchBranches($user)) {
            return false;
        }

        // Verify branch exists
        $branch = Branch::find($branch_id);
        if (!$branch) {
            return false;
        }

        Session::put(self::SESSION_KEY, $branch_id);

        return true;
    }

    /**
     * Clear the current branch from session (revert to user's default branch).
     */
    public function clearCurrentBranch(): void
    {
        Session::forget(self::SESSION_KEY);
    }

    /**
     * Check if user can switch branches.
     */
    public function canSwitchBranches(?User $user = null): bool
    {
        $user = $user ?? Auth::user();

        if (!$user instanceof User) {
            return false;
        }

        return $user->hasRole('super-admin') || $user->hasPermissionTo('switch branches');
    }

    /**
     * Check if user has access to a specific branch.
     */
    public function hasAccessToBranch(int $branch_id, ?User $user = null): bool
    {
        $user = $user ?? Auth::user();

        if (!$user instanceof User) {
            return false;
        }

        // Super admin has access to all branches
        if ($user->hasRole('super-admin')) {
            return true;
        }

        // Users with switch permission can access any branch
        if ($user->hasPermissionTo('switch branches')) {
            return true;
        }

        // Regular users can only access their assigned branch
        return $user->branch_id === $branch_id;
    }

    /**
     * Check if the given model belongs to the current branch.
     */
    public function belongsToCurrentBranch($model): bool
    {
        if (!property_exists($model, 'branch_id') && !isset($model->branch_id)) {
            return true; // Model is not branch-scoped
        }

        $current_branch_id = $this->getCurrentBranchId();

        if ($current_branch_id === null) {
            // No branch context set, allow access for super-admin
            $user = Auth::user();
            return $user instanceof User && $user->hasRole('super-admin');
        }

        return $model->branch_id === $current_branch_id;
    }

    /**
     * Get all branches the user has access to.
     */
    public function getAccessibleBranches(?User $user = null): \Illuminate\Database\Eloquent\Collection
    {
        $user = $user ?? Auth::user();

        if (!$user instanceof User) {
            return Branch::query()->whereRaw('1 = 0')->get(); // Empty collection
        }

        // Super admin or users with switch permission see all branches
        if ($user->hasRole('super-admin') || $user->hasPermissionTo('switch branches')) {
            return Branch::active()->get();
        }

        // Regular users only see their branch
        if ($user->branch_id) {
            return Branch::where('id', $user->branch_id)->get();
        }

        return Branch::query()->whereRaw('1 = 0')->get(); // Empty collection
    }
}

