<?php

namespace App\Livewire\EquipmentAllocations;

use App\Models\Branch;
use App\Models\Equipment;
use App\Models\EquipmentAllocation;
use App\Models\Location;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Form extends Component
{
    public ?EquipmentAllocation $allocation = null;
    public bool $is_editing = false;

    public ?int $branch_id = null;
    public ?int $location_id = null;
    public ?int $equipment_id = null;
    public ?string $asset_tag = null;
    public int $quantity = 1;
    public bool $is_active = true;

    public function mount(?EquipmentAllocation $equipmentAllocation = null): void
    {
        $this->allocation = $equipmentAllocation;
        $this->is_editing = $equipmentAllocation && $equipmentAllocation->exists;

        if ($this->is_editing) {
            $this->fill([
                'branch_id' => $equipmentAllocation->branch_id,
                'location_id' => $equipmentAllocation->location_id,
                'equipment_id' => $equipmentAllocation->equipment_id,
                'asset_tag' => $equipmentAllocation->asset_tag,
                'quantity' => $equipmentAllocation->quantity,
                'is_active' => $equipmentAllocation->is_active,
            ]);
        } else {
            $this->is_active = true;
            $this->quantity = 1;
        }

        if (Auth::user()?->hasRole('super-admin') && !$this->branch_id) {
            $this->branch_id = null;
        } else {
            $this->branch_id = $this->branch_id ?: Auth::user()?->branch_id;
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
            'location_id' => ['required', 'exists:locations,id'],
            'equipment_id' => ['required', 'exists:equipment,id'],
            'asset_tag' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('equipment_allocations', 'asset_tag')->ignore($this->allocation?->id),
            ],
            'quantity' => ['required', 'integer', 'min:1', 'max:1000'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'location_id.required' => __('Please select a location.'),
            'equipment_id.required' => __('Please select equipment.'),
            'asset_tag.unique' => __('This asset tag is already in use.'),
        ];
    }

    public function save(): void
    {
        $this->authorize($this->is_editing ? 'update' : 'create', $this->allocation ?? EquipmentAllocation::class);
        $data = $this->validate();

        if (!isset($data['branch_id']) || $data['branch_id'] === null) {
            $data['branch_id'] = $this->branch_id ?? Auth::user()?->branch_id;
        }

        DB::beginTransaction();

        try {
            if (!$this->is_editing) {
                EquipmentAllocation::create($data);

                DB::commit();
                session()->flash('success', __('Equipment allocation created successfully.'));
                $this->redirect(route('equipment-allocations.index'), navigate: true);
            } else {
                $this->allocation->update($data);

                DB::commit();
                session()->flash('success', __('Equipment allocation updated successfully.'));
                $this->redirect(route('equipment-allocations.index'), navigate: true);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', __('An error occurred while saving. Please try again.'));
        }
    }

    public function render(): View
    {
        $branches = Auth::user()->hasRole('super-admin')
            ? Branch::orderBy('name')->get(['id', 'name'])
            : collect();

        $current_branch_id = $this->branch_id ?? Auth::user()?->branch_id;

        $locations = Location::active()
            ->when($current_branch_id, fn($q) => $q->where('branch_id', $current_branch_id))
            ->orderBy('name')
            ->get(['id', 'name']);

        $equipment_list = Equipment::orderBy('name')->get(['id', 'name', 'type', 'brand']);

        // Get selected location's current equipment
        $location_equipment = [];
        if ($this->location_id) {
            $location_equipment = EquipmentAllocation::query()
                ->where('location_id', $this->location_id)
                ->when($this->is_editing, fn($q) => $q->where('id', '!=', $this->allocation->id))
                ->with('equipment')
                ->get();
        }

        return view('livewire.equipment-allocations.form', [
            'branches' => $branches,
            'locations' => $locations,
            'equipment_list' => $equipment_list,
            'location_equipment' => $location_equipment,
        ]);
    }
}

