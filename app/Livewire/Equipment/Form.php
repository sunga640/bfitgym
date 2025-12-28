<?php

namespace App\Livewire\Equipment;

use App\Models\Equipment;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Form extends Component
{
    public ?Equipment $equipment = null;
    public bool $is_editing = false;
    public string $name = '';
    public string $description = '';
    public string $type = '';
    public string $brand = '';
    public string $model = '';

    public function mount(?Equipment $equipment = null): void
    {
        $this->equipment = $equipment;
        $this->is_editing = $equipment && $equipment->exists;

        if ($this->is_editing) {
            $this->fill([
                'name' => $equipment->name,
                'description' => $equipment->description ?? '',
                'type' => $equipment->type ?? '',
                'brand' => $equipment->brand ?? '',
                'model' => $equipment->model ?? '',
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('equipment', 'name')->ignore($this->equipment?->id),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'type' => ['nullable', 'string', 'max:100'],
            'brand' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => __('Please enter an equipment name.'),
            'name.unique' => __('Equipment with this name already exists.'),
            'name.max' => __('Equipment name cannot exceed 150 characters.'),
            'description.max' => __('Description cannot exceed 2000 characters.'),
            'type.max' => __('Type cannot exceed 100 characters.'),
            'brand.max' => __('Brand cannot exceed 100 characters.'),
            'model.max' => __('Model cannot exceed 100 characters.'),
        ];
    }

    public function save(): void
    {
        $this->authorize($this->is_editing ? 'update' : 'create', $this->equipment ?? Equipment::class);
        $data = $this->validate();

        DB::beginTransaction();

        try {
            if (!$this->is_editing) {
                Equipment::create($data);

                DB::commit();
                session()->flash('success', __('Equipment created successfully.'));
                $this->redirect(route('equipment.index'), navigate: true);
            } else {
                $this->equipment->update($data);

                DB::commit();
                session()->flash('success', __('Equipment updated successfully.'));
                $this->redirect(route('equipment.index'), navigate: true);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', __('An error occurred while saving the equipment. Please try again.'));
        }
    }

    public function render(): View
    {
        // Get distinct types for suggestions
        $existing_types = Equipment::query()
            ->whereNotNull('type')
            ->where('type', '!=', '')
            ->distinct()
            ->orderBy('type')
            ->pluck('type');

        return view('livewire.equipment.form', [
            'existing_types' => $existing_types,
        ]);
    }
}

