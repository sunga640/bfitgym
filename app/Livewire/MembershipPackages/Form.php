<?php

namespace App\Livewire\MembershipPackages;

use App\Models\MembershipPackage;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Form extends Component
{
    public ?MembershipPackage $package = null;

    public string $name = '';
    public string $description = '';
    public string $price = '';
    public string $duration_type = 'months';
    public int $duration_value = 1;
    public bool $is_renewable = true;
    public string $status = 'active';

    public bool $is_editing = false;
    public bool $needs_branch_selection = false;

    public function mount(?MembershipPackage $membershipPackage = null): void
    {
        // Check branch context
        if (!current_branch_id()) {
            // For super-admins, try to auto-select first available branch
            $user = auth()->user();
            if ($user && $user->hasRole('super-admin')) {
                $first_branch = \App\Models\Branch::active()->first();
                if ($first_branch) {
                    branch_context()->setCurrentBranch($first_branch->id);
                } else {
                    $this->needs_branch_selection = true;
                    return;
                }
            } else {
                $this->needs_branch_selection = true;
                return;
            }
        }

        if ($membershipPackage && $membershipPackage->exists) {
            $this->authorize('update', $membershipPackage);

            $this->package = $membershipPackage;
            $this->is_editing = true;
            $this->name = $membershipPackage->name;
            $this->description = $membershipPackage->description ?? '';
            $this->price = (string) $membershipPackage->price;
            $this->duration_type = $membershipPackage->duration_type;
            $this->duration_value = $membershipPackage->duration_value;
            $this->is_renewable = (bool) $membershipPackage->is_renewable;
            $this->status = $membershipPackage->status;
        } else {
            $this->authorize('create', MembershipPackage::class);
        }
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('membership_packages', 'name')
                    ->where('branch_id', current_branch_id())
                    ->whereNull('deleted_at')
                    ->ignore($this->package?->id),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'duration_type' => ['required', Rule::in(['days', 'weeks', 'months', 'years'])],
            'duration_value' => ['required', 'integer', 'min:1', 'max:365'],
            'is_renewable' => ['boolean'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => __('Package name is required.'),
            'name.unique' => __('A package with this name already exists in this branch.'),
            'name.max' => __('Package name cannot exceed 150 characters.'),
            'price.required' => __('Price is required.'),
            'price.numeric' => __('Price must be a valid number.'),
            'price.min' => __('Price cannot be negative.'),
            'price.max' => __('Price is too large.'),
            'duration_type.required' => __('Please select a duration type.'),
            'duration_type.in' => __('Invalid duration type selected.'),
            'duration_value.required' => __('Duration value is required.'),
            'duration_value.min' => __('Duration must be at least 1.'),
            'duration_value.max' => __('Duration cannot exceed 365.'),
            'status.required' => __('Please select a status.'),
            'status.in' => __('Invalid status selected.'),
        ];
    }

    /**
     * Real-time validation for specific fields.
     */
    public function updated(string $property): void
    {
        if (in_array($property, ['name', 'price', 'duration_value'])) {
            $this->validateOnly($property);
        }
    }

    public function save(): void
    {
        // Safety check for branch context
        if (!current_branch_id()) {
            session()->flash('error', __('No branch selected. Please select a branch first.'));
            return;
        }

        $validated = $this->validate();

        try {
            DB::beginTransaction();

            if ($this->is_editing) {
                $this->updatePackage($validated);
            } else {
                $this->createPackage($validated);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to save membership package', [
                'error' => $e->getMessage(),
                'branch_id' => current_branch_id(),
                'user_id' => auth()->id(),
                'data' => $validated,
            ]);

            session()->flash('error', __('Failed to save the package. Please try again.'));
        }
    }

    protected function createPackage(array $validated): void
    {
        $package = MembershipPackage::create([
            'branch_id' => current_branch_id(),
            'name' => trim($validated['name']),
            'description' => $validated['description'] ? trim($validated['description']) : null,
            'price' => $validated['price'],
            'duration_type' => $validated['duration_type'],
            'duration_value' => $validated['duration_value'],
            'is_renewable' => $validated['is_renewable'] ?? true,
            'status' => $validated['status'],
        ]);

        Log::info('Membership package created', [
            'package_id' => $package->id,
            'branch_id' => $package->branch_id,
            'user_id' => auth()->id(),
        ]);

        session()->flash('success', __('Membership package ":name" created successfully.', ['name' => $package->name]));

        $this->redirect(route('membership-packages.index'), navigate: true);
    }

    protected function updatePackage(array $validated): void
    {
        $old_name = $this->package->name;

        $this->package->update([
            'name' => trim($validated['name']),
            'description' => $validated['description'] ? trim($validated['description']) : null,
            'price' => $validated['price'],
            'duration_type' => $validated['duration_type'],
            'duration_value' => $validated['duration_value'],
            'is_renewable' => $validated['is_renewable'] ?? true,
            'status' => $validated['status'],
        ]);

        Log::info('Membership package updated', [
            'package_id' => $this->package->id,
            'branch_id' => $this->package->branch_id,
            'user_id' => auth()->id(),
            'old_name' => $old_name,
            'new_name' => $this->package->name,
        ]);

        session()->flash('success', __('Membership package ":name" updated successfully.', ['name' => $this->package->name]));

        $this->redirect(route('membership-packages.index'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.membership-packages.form', [
            'duration_types' => [
                'days' => __('Days'),
                'weeks' => __('Weeks'),
                'months' => __('Months'),
                'years' => __('Years'),
            ],
        ]);
    }
}
