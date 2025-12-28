<?php

namespace App\Livewire\EquipmentAllocations;

use App\Models\Equipment;
use App\Models\EquipmentAllocation;
use App\Models\Location;
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
    public string $location_filter = '';

    #[Url]
    public string $equipment_filter = '';

    #[Url]
    public string $status_filter = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingLocationFilter(): void
    {
        $this->resetPage();
    }

    public function updatingEquipmentFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function toggleStatus(int $allocation_id): void
    {
        try {
            $allocation = EquipmentAllocation::findOrFail($allocation_id);

            $this->authorize('update', $allocation);

            $allocation->update(['is_active' => !$allocation->is_active]);

            session()->flash('success', __('Allocation status updated.'));
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            session()->flash('error', __('You do not have permission to update this allocation.'));
        } catch (\Exception $e) {
            session()->flash('error', __('Failed to update allocation status.'));
        }
    }

    public function deleteAllocation(int $allocation_id): void
    {
        try {
            $allocation = EquipmentAllocation::findOrFail($allocation_id);

            $this->authorize('delete', $allocation);

            DB::beginTransaction();
            $allocation->delete();
            DB::commit();

            Log::info('Equipment allocation deleted', [
                'allocation_id' => $allocation_id,
                'user_id' => auth()->id(),
            ]);

            session()->flash('success', __('Equipment allocation deleted successfully.'));
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            session()->flash('error', __('You do not have permission to delete this allocation.'));
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', __('Failed to delete allocation.'));
        }
    }

    public function render(): View
    {
        $allocations = EquipmentAllocation::query()
            ->with(['location', 'equipment'])
            ->when($this->search, function ($query) {
                $query->whereHas('equipment', fn($q) => $q->where('name', 'like', "%{$this->search}%"))
                    ->orWhereHas('location', fn($q) => $q->where('name', 'like', "%{$this->search}%"))
                    ->orWhere('asset_tag', 'like', "%{$this->search}%");
            })
            ->when($this->location_filter, fn($query) => $query->where('location_id', $this->location_filter))
            ->when($this->equipment_filter, fn($query) => $query->where('equipment_id', $this->equipment_filter))
            ->when($this->status_filter !== '', fn($query) => $query->where('is_active', $this->status_filter === '1'))
            ->latest()
            ->paginate(15);

        $user = Auth::user();
        $show_branch = $user && $user->hasRole('super-admin');

        $locations = Location::active()->orderBy('name')->get(['id', 'name']);
        $equipment_list = Equipment::orderBy('name')->get(['id', 'name']);

        // Get equipment usage summary by location
        $location_summary = EquipmentAllocation::query()
            ->select('location_id', DB::raw('COUNT(*) as total_items'), DB::raw('SUM(quantity) as total_quantity'))
            ->active()
            ->groupBy('location_id')
            ->with('location')
            ->get();

        return view('livewire.equipment-allocations.index', [
            'allocations' => $allocations,
            'show_branch' => $show_branch,
            'locations' => $locations,
            'equipment_list' => $equipment_list,
            'location_summary' => $location_summary,
        ]);
    }
}

