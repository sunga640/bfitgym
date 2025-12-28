<?php

namespace App\Livewire\ClassSessions;

use App\Models\Branch;
use App\Models\ClassSession;
use App\Models\ClassType;
use App\Models\Location;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Form extends Component
{
    public ?ClassSession $class_session = null;
    public bool $is_editing = false;

    public ?int $branch_id = null;
    public ?int $class_type_id = null;
    public ?int $location_id = null;
    public ?int $main_instructor_id = null;
    public array $assistant_staff_ids = [];

    public ?int $day_of_week = null;
    public ?string $specific_date = null;
    public string $start_time = '';
    public string $end_time = '';
    public ?int $capacity_override = null;
    public bool $is_recurring = true;
    public string $status = 'active';

    public function mount(?ClassSession $classSession = null): void
    {
        $this->class_session = $classSession;
        $this->is_editing = $classSession && $classSession->exists;

        if ($this->is_editing) {
            $this->fill([
                'branch_id' => $classSession->branch_id,
                'class_type_id' => $classSession->class_type_id,
                'location_id' => $classSession->location_id,
                'main_instructor_id' => $classSession->main_instructor_id,
                'assistant_staff_ids' => $classSession->assistantStaff->pluck('id')->toArray(),
                'day_of_week' => $classSession->day_of_week,
                'specific_date' => $classSession->specific_date?->format('Y-m-d'),
                'start_time' => $classSession->start_time?->format('H:i') ?? '',
                'end_time' => $classSession->end_time?->format('H:i') ?? '',
                'capacity_override' => $classSession->capacity_override,
                'is_recurring' => $classSession->is_recurring,
                'status' => $classSession->status,
            ]);
        } else {
            $this->status = 'active';
            $this->is_recurring = true;
        }

        if (Auth::user()?->hasRole('super-admin') && !$this->branch_id) {
            $this->branch_id = null;
        } else {
            $this->branch_id = $this->branch_id ?: Auth::user()?->branch_id;
        }
    }

    public function updatedIsRecurring(): void
    {
        if ($this->is_recurring) {
            $this->specific_date = null;
        } else {
            $this->day_of_week = null;
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
            'class_type_id' => ['required', 'exists:class_types,id'],
            'location_id' => ['required', 'exists:locations,id'],
            'main_instructor_id' => ['required', 'exists:users,id'],
            'assistant_staff_ids' => ['nullable', 'array'],
            'assistant_staff_ids.*' => ['exists:users,id'],
            'day_of_week' => [
                Rule::requiredIf(fn() => $this->is_recurring),
                'nullable',
                'integer',
                'min:1',
                'max:7'
            ],
            'specific_date' => [
                Rule::requiredIf(fn() => !$this->is_recurring),
                'nullable',
                'date'
            ],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'capacity_override' => ['nullable', 'integer', 'min:1', 'max:500'],
            'is_recurring' => ['boolean'],
            'status' => ['required', Rule::in(['active', 'cancelled'])],
        ];
    }

    public function messages(): array
    {
        return [
            'class_type_id.required' => __('Please select a class type.'),
            'location_id.required' => __('Please select a location.'),
            'main_instructor_id.required' => __('Please select a main instructor.'),
            'day_of_week.required' => __('Please select a day of the week for recurring sessions.'),
            'specific_date.required' => __('Please select a specific date for one-time sessions.'),
            'start_time.required' => __('Please enter a start time.'),
            'end_time.required' => __('Please enter an end time.'),
            'end_time.after' => __('End time must be after start time.'),
        ];
    }

    public function save(): void
    {
        $this->authorize($this->is_editing ? 'update' : 'create', $this->class_session ?? ClassSession::class);
        $data = $this->validate();

        if (!isset($data['branch_id']) || $data['branch_id'] === null) {
            $data['branch_id'] = $this->branch_id ?? Auth::user()?->branch_id;
        }

        // Clean up based on recurring flag
        if ($data['is_recurring']) {
            $data['specific_date'] = null;
        } else {
            $data['day_of_week'] = null;
        }

        DB::beginTransaction();

        try {
            $assistant_ids = $data['assistant_staff_ids'] ?? [];
            unset($data['assistant_staff_ids']);

            if (!$this->is_editing) {
                $session = ClassSession::create($data);
                $session->assistantStaff()->sync($assistant_ids);

                DB::commit();
                session()->flash('success', __('Class session created successfully.'));
                $this->redirect(route('class-sessions.index'), navigate: true);
            } else {
                $this->class_session->update($data);
                $this->class_session->assistantStaff()->sync($assistant_ids);

                DB::commit();
                session()->flash('success', __('Class session updated successfully.'));
                $this->redirect(route('class-sessions.index'), navigate: true);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', __('An error occurred while saving the session. Please try again.'));
        }
    }

    public function render(): View
    {
        $branches = Auth::user()->hasRole('super-admin')
            ? Branch::orderBy('name')->get(['id', 'name'])
            : collect();

        // Filter by branch for branch-scoped entities
        $current_branch_id = $this->branch_id ?? Auth::user()?->branch_id;

        $class_types = ClassType::active()
            ->when($current_branch_id, fn($q) => $q->where('branch_id', $current_branch_id))
            ->orderBy('name')
            ->get(['id', 'name', 'capacity', 'has_booking_fee', 'booking_fee']);

        $locations = Location::active()
            ->when($current_branch_id, fn($q) => $q->where('branch_id', $current_branch_id))
            ->orderBy('name')
            ->get(['id', 'name']);

        // Get trainers - users with trainer role or from the branch
        $instructors = User::query()
            ->when($current_branch_id, fn($q) => $q->where('branch_id', $current_branch_id))
            ->orderBy('name')
            ->get(['id', 'name']);

        $days = [
            1 => __('Monday'),
            2 => __('Tuesday'),
            3 => __('Wednesday'),
            4 => __('Thursday'),
            5 => __('Friday'),
            6 => __('Saturday'),
            7 => __('Sunday'),
        ];

        // Get selected class type info for display
        $selected_class_type = $this->class_type_id
            ? $class_types->firstWhere('id', $this->class_type_id)
            : null;

        return view('livewire.class-sessions.form', [
            'branches' => $branches,
            'class_types' => $class_types,
            'locations' => $locations,
            'instructors' => $instructors,
            'days' => $days,
            'selected_class_type' => $selected_class_type,
        ]);
    }
}

