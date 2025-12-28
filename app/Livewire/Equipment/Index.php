<?php

namespace App\Livewire\Equipment;

use App\Models\Equipment;
use Illuminate\Contracts\View\View;
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
    public string $type_filter = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingTypeFilter(): void
    {
        $this->resetPage();
    }

    public function deleteEquipment(int $equipment_id): void
    {
        try {
            $equipment = Equipment::findOrFail($equipment_id);

            $this->authorize('delete', $equipment);

            $equipment_name = $equipment->name;

            // Check if equipment has allocations
            if ($equipment->allocations()->exists()) {
                session()->flash('error', __('Cannot delete equipment ":name" because it has associated allocations.', ['name' => $equipment_name]));
                return;
            }

            DB::beginTransaction();
            $equipment->delete();
            DB::commit();

            Log::info('Equipment deleted', [
                'equipment_id' => $equipment_id,
                'equipment_name' => $equipment_name,
                'user_id' => auth()->id(),
            ]);

            session()->flash('success', __('Equipment ":name" deleted successfully.', ['name' => $equipment_name]));
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            session()->flash('error', __('You do not have permission to delete this equipment.'));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            session()->flash('error', __('Equipment not found.'));
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to delete equipment', [
                'equipment_id' => $equipment_id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            session()->flash('error', __('Failed to delete the equipment. Please try again.'));
        }
    }

    public function render(): View
    {
        $equipment = Equipment::query()
            ->withCount('allocations')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', "%{$this->search}%")
                        ->orWhere('description', 'like', "%{$this->search}%")
                        ->orWhere('brand', 'like', "%{$this->search}%")
                        ->orWhere('model', 'like', "%{$this->search}%");
                });
            })
            ->when($this->type_filter, fn($query) => $query->where('type', $this->type_filter))
            ->latest()
            ->paginate(12);

        // Get distinct types for filter dropdown
        $types = Equipment::query()
            ->whereNotNull('type')
            ->where('type', '!=', '')
            ->distinct()
            ->orderBy('type')
            ->pluck('type');

        return view('livewire.equipment.index', [
            'equipment' => $equipment,
            'types' => $types,
        ]);
    }
}

