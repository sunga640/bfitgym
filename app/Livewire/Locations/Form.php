<?php

namespace App\Livewire\Locations;

use App\Models\Branch;
use App\Models\Location;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Form extends Component
{
    public ?Location $location = null;
    public bool $is_editing = false;
    public ?int $branch_id = null;
    public string $name = '';
    public string $description = '';
    public bool $is_active = true;

    public function mount(?Location $location = null): void
    {
        $this->location = $location;
        $this->is_editing = $location && $location->exists;

        if ($this->is_editing) {
            $this->fill([
                'branch_id' => $location->branch_id,
                'name' => $location->name,
                'description' => $location->description ?? '',
                'is_active' => $location->is_active,
            ]);
        } else {
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
                'max:100',
                Rule::unique('locations', 'name')
                    ->where('branch_id', $this->branch_id ?? Auth::user()?->branch_id)
                    ->ignore($this->location?->id),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => __('Please enter a location name.'),
            'name.unique' => __('A location with this name already exists in the selected branch.'),
            'name.max' => __('Location name cannot exceed 100 characters.'),
            'description.max' => __('Description cannot exceed 1000 characters.'),
        ];
    }

    public function save(): void
    {
        $this->authorize($this->is_editing ? 'update' : 'create', $this->location ?? Location::class);
        $data = $this->validate();

        if (!isset($data['branch_id']) || $data['branch_id'] === null) {
            $data['branch_id'] = $this->branch_id ?? Auth::user()?->branch_id;
        }

        DB::beginTransaction();

        try {
            if (!$this->is_editing) {
                Location::create($data);

                DB::commit();
                session()->flash('success', __('Location created successfully.'));
                $this->redirect(route('locations.index'), navigate: true);
            } else {
                $this->location->update($data);

                DB::commit();
                session()->flash('success', __('Location updated successfully.'));
                $this->redirect(route('locations.index'), navigate: true);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', __('An error occurred while saving the location. Please try again.'));
        }
    }

    public function render(): View
    {
        $branches = Auth::user()->hasRole('super-admin')
            ? Branch::orderBy('name')->get(['id', 'name'])
            : collect();

        return view('livewire.locations.form', [
            'branches' => $branches,
        ]);
    }
}

