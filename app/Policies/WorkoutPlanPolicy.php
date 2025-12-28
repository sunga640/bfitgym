<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkoutPlan;

class WorkoutPlanPolicy extends BranchScopedPolicy
{
    /**
     * Determine whether the user can view any workout plans.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view workout plans') && $this->hasCurrentBranchAccess($user);
    }

    /**
     * Determine whether the user can view the workout plan.
     */
    public function view(User $user, WorkoutPlan $workout_plan): bool
    {
        return $user->hasPermissionTo('view workout plans') && $this->belongsToUserBranch($user, $workout_plan);
    }

    /**
     * Determine whether the user can create workout plans.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create workout plans') && $this->hasCurrentBranchAccess($user);
    }

    /**
     * Determine whether the user can update the workout plan.
     */
    public function update(User $user, WorkoutPlan $workout_plan): bool
    {
        return $user->hasPermissionTo('edit workout plans') && $this->belongsToUserBranch($user, $workout_plan);
    }

    /**
     * Determine whether the user can delete the workout plan.
     */
    public function delete(User $user, WorkoutPlan $workout_plan): bool
    {
        return $user->hasPermissionTo('delete workout plans') && $this->belongsToUserBranch($user, $workout_plan);
    }

    /**
     * Determine whether the user can restore the workout plan.
     */
    public function restore(User $user, WorkoutPlan $workout_plan): bool
    {
        return $user->hasPermissionTo('edit workout plans') && $this->belongsToUserBranch($user, $workout_plan);
    }

    /**
     * Determine whether the user can permanently delete the workout plan.
     */
    public function forceDelete(User $user, WorkoutPlan $workout_plan): bool
    {
        return $user->hasPermissionTo('delete workout plans') && $this->belongsToUserBranch($user, $workout_plan);
    }
}

