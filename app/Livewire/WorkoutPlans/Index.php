<?php

namespace App\Livewire\WorkoutPlans;

use App\Models\WorkoutPlan;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $level_filter = '';

    #[Url]
    public string $status_filter = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingLevelFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function deleteWorkoutPlan(int $workout_plan_id): void
    {
        try {
            $workout_plan = WorkoutPlan::findOrFail($workout_plan_id);

            $this->authorize('delete', $workout_plan);

            $workout_plan_name = $workout_plan->name;

            // Check if workout plan is assigned to any members
            if ($workout_plan->memberWorkoutPlans()->exists()) {
                session()->flash('error', __('Cannot delete workout plan ":name" because it is assigned to members.', ['name' => $workout_plan_name]));
                return;
            }

            DB::beginTransaction();
            $workout_plan->delete();
            DB::commit();

            Log::info('Workout plan deleted', [
                'workout_plan_id' => $workout_plan_id,
                'workout_plan_name' => $workout_plan_name,
                'branch_id' => $workout_plan->branch_id,
                'user_id' => auth()->id(),
            ]);

            session()->flash('success', __('Workout plan ":name" deleted successfully.', ['name' => $workout_plan_name]));
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            session()->flash('error', __('You do not have permission to delete this workout plan.'));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            session()->flash('error', __('Workout plan not found.'));
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to delete workout plan', [
                'workout_plan_id' => $workout_plan_id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            session()->flash('error', __('Failed to delete the workout plan. Please try again.'));
        }
    }

    public function render(): View
    {
        $workout_plans = WorkoutPlan::query()
            ->with(['branch'])
            ->withCount(['days', 'memberWorkoutPlans'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', "%{$this->search}%")
                        ->orWhere('description', 'like', "%{$this->search}%");
                });
            })
            ->when($this->level_filter, fn($query) => $query->where('level', $this->level_filter))
            ->when($this->status_filter !== '', fn($query) => $query->where('is_active', $this->status_filter === '1'))
            ->latest()
            ->paginate(12);

        $user = Auth::user();
        $show_branch = $user && $user->hasRole('super-admin');

        return view('livewire.workout-plans.index', [
            'workout_plans' => $workout_plans,
            'show_branch' => $show_branch,
        ]);
    }
}

