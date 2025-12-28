<?php

namespace App\Livewire\ClassTypes;

use App\Models\Branch;
use App\Models\ClassType;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Form extends Component
{
    public ?ClassType $class_type = null;
    public bool $is_editing = false;
    public ?int $branch_id = null;
    public string $name = '';
    public string $description = '';
    public ?int $capacity = null;
    public bool $has_booking_fee = false;
    public ?string $booking_fee = null;
    public string $status = 'active';

    public function mount(?ClassType $classType = null): void
    {
        $this->class_type = $classType;
        $this->is_editing = $classType && $classType->exists;

        if ($this->is_editing) {
            $this->fill([
                'branch_id' => $classType->branch_id,
                'name' => $classType->name,
                'description' => $classType->description ?? '',
                'capacity' => $classType->capacity,
                'has_booking_fee' => $classType->has_booking_fee,
                'booking_fee' => $classType->booking_fee ? number_format($classType->booking_fee, 2, '.', '') : null,
                'status' => $classType->status,
            ]);
        } else {
            $this->status = 'active';
        }

        if (Auth::user()?->hasRole('super-admin') && !$this->branch_id) {
            $this->branch_id = null;
        } else {
            $this->branch_id = $this->branch_id ?: (Auth::user()?->branch_id);
        }
    }

    public function updatedHasBookingFee(): void
    {
        // Clear booking fee when unchecked
        if (!$this->has_booking_fee) {
            $this->booking_fee = null;
        }
    }

    public function rules(): array
    {
        $rules = [
            'branch_id' => [
                Rule::requiredIf(fn() => Auth::user()?->hasRole('super-admin')),
                'nullable',
                'exists:branches,id'
            ],
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('class_types', 'name')
                    ->where('branch_id', $this->branch_id ?? Auth::user()?->branch_id)
                    ->ignore($this->class_type?->id),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'has_booking_fee' => ['boolean'],
            'booking_fee' => [
                Rule::requiredIf(fn() => $this->has_booking_fee),
                'nullable',
                'numeric',
                'min:0',
                'max:9999999.99'
            ],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];

        return $rules;
    }

    public function messages(): array
    {
        return [
            'name.unique' => __('A class type with this name already exists in the selected branch.'),
            'booking_fee.required' => __('Please enter the booking fee amount.'),
            'booking_fee.min' => __('Booking fee must be a positive amount.'),
            'capacity.min' => __('Capacity must be at least 1.'),
        ];
    }

    public function save(): void
    {
        $this->authorize($this->is_editing ? 'update' : 'create', $this->class_type ?? ClassType::class);
        $data = $this->validate();

        if (!isset($data['branch_id']) || $data['branch_id'] === null) {
            $data['branch_id'] = $this->branch_id ?? Auth::user()?->branch_id;
        }

        // Ensure booking_fee is null if has_booking_fee is false
        if (!$data['has_booking_fee']) {
            $data['booking_fee'] = null;
        }

        DB::beginTransaction();

        try {
            if (!$this->is_editing) {
                ClassType::create($data);

                DB::commit();
                session()->flash('success', __('Class type created successfully.'));
                $this->redirect(route('class-types.index'), navigate: true);
            } else {
                $this->class_type->update($data);

                DB::commit();
                session()->flash('success', __('Class type updated successfully.'));
                $this->redirect(route('class-types.index'), navigate: true);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', __('An error occurred while saving the class type. Please try again.'));
        }
    }

    public function render(): View
    {
        $branches = Auth::user()->hasRole('super-admin')
            ? Branch::orderBy('name')->get(['id', 'name'])
            : collect();

        return view('livewire.class-types.form', [
            'branches' => $branches,
        ]);
    }
}

