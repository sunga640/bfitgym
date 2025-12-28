<?php

namespace App\Livewire\WorkoutPlans;

use App\Models\Branch;
use App\Models\WorkoutPlan;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Form extends Component
{
    public ?WorkoutPlan $workout_plan = null;
    public bool $is_editing = false;
    public ?int $branch_id = null;
    public string $name = '';
    public string $level = 'beginner';
    public string $description = '';
    public ?int $total_weeks = null;
    public bool $is_active = true;

    public function mount(?WorkoutPlan $workoutPlan = null): void
    {
        $this->workout_plan = $workoutPlan;
        $this->is_editing = $workoutPlan && $workoutPlan->exists;

        if ($this->is_editing) {
            $this->fill([
                'branch_id' => $workoutPlan->branch_id,
                'name' => $workoutPlan->name,
                'level' => $workoutPlan->level,
                'description' => $workoutPlan->description ?? '',
                'total_weeks' => $workoutPlan->total_weeks,
                'is_active' => $workoutPlan->is_active,
            ]);
        } else {
            $this->level = 'beginner';
            $this->is_active = true;
        }

        if (Auth::user()?->hasRole('super-admin') && !$this->branch_id) {
            $this->branch_id = null;
        } else {
            $this->branch_id = $this->branch_id ?: (Auth::user()?->branch_id);
        }
    }

    public function rules(): array
    {
        return [
            'branch_id' => [
                Rule::requiredIf(fn() => Auth::user()?->hasRole('super-admin')),
                'nullable',
                'exists:branches,id'
            ],
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('workout_plans', 'name')
                    ->where('branch_id', $this->branch_id ?? Auth::user()?->branch_id)
                    ->ignore($this->workout_plan?->id),
            ],
            'level' => ['required', Rule::in(['beginner', 'intermediate', 'advanced'])],
            'description' => ['nullable', 'string', 'max:2000'],
            'total_weeks' => ['nullable', 'integer', 'min:1', 'max:52'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => __('A workout plan with this name already exists in the selected branch.'),
            'name.required' => __('Please enter a name for the workout plan.'),
            'level.required' => __('Please select a difficulty level.'),
            'total_weeks.min' => __('Duration must be at least 1 week.'),
            'total_weeks.max' => __('Duration cannot exceed 52 weeks.'),
        ];
    }

    public function save(): void
    {
        $this->authorize($this->is_editing ? 'update' : 'create', $this->workout_plan ?? WorkoutPlan::class);
        $data = $this->validate();

        if (!isset($data['branch_id']) || $data['branch_id'] === null) {
            $data['branch_id'] = $this->branch_id ?? Auth::user()?->branch_id;
        }

        DB::beginTransaction();

        try {
            if (!$this->is_editing) {
                WorkoutPlan::create($data);

                DB::commit();
                session()->flash('success', __('Workout plan created successfully.'));
                $this->redirect(route('workout-plans.index'), navigate: true);
            } else {
                $this->workout_plan->update($data);

                DB::commit();
                session()->flash('success', __('Workout plan updated successfully.'));
                $this->redirect(route('workout-plans.index'), navigate: true);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', __('An error occurred while saving the workout plan. Please try again.'));
        }
    }

    public function render(): View
    {
        $branches = Auth::user()->hasRole('super-admin')
            ? Branch::orderBy('name')->get(['id', 'name'])
            : collect();

        return view('livewire.workout-plans.form', [
            'branches' => $branches,
            'levels' => [
                'beginner' => __('Beginner'),
                'intermediate' => __('Intermediate'),
                'advanced' => __('Advanced'),
            ],
        ]);
    }
}

