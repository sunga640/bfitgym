<?php

namespace App\Livewire\Locations;

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
    public string $status_filter = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function deleteLocation(int $location_id): void
    {
        try {
            $location = Location::findOrFail($location_id);

            $this->authorize('delete', $location);

            $location_name = $location->name;

            // Check if location has class sessions
            if ($location->classSessions()->exists()) {
                session()->flash('error', __('Cannot delete location ":name" because it has associated class sessions.', ['name' => $location_name]));
                return;
            }

            // Check if location has equipment allocations
            if ($location->equipmentAllocations()->exists()) {
                session()->flash('error', __('Cannot delete location ":name" because it has associated equipment allocations.', ['name' => $location_name]));
                return;
            }

            // Check if location has access control devices
            if ($location->accessControlDevices()->exists()) {
                session()->flash('error', __('Cannot delete location ":name" because it has associated access control devices.', ['name' => $location_name]));
                return;
            }

            DB::beginTransaction();
            $location->delete();
            DB::commit();

            Log::info('Location deleted', [
                'location_id' => $location_id,
                'location_name' => $location_name,
                'branch_id' => $location->branch_id,
                'user_id' => auth()->id(),
            ]);

            session()->flash('success', __('Location ":name" deleted successfully.', ['name' => $location_name]));
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            session()->flash('error', __('You do not have permission to delete this location.'));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            session()->flash('error', __('Location not found.'));
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to delete location', [
                'location_id' => $location_id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            session()->flash('error', __('Failed to delete the location. Please try again.'));
        }
    }

    public function render(): View
    {
        $locations = Location::query()
            ->with(['branch'])
            ->withCount(['classSessions', 'equipmentAllocations', 'accessControlDevices'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', "%{$this->search}%")
                        ->orWhere('description', 'like', "%{$this->search}%");
                });
            })
            ->when($this->status_filter !== '', fn($query) => $query->where('is_active', $this->status_filter === '1'))
            ->latest()
            ->paginate(12);

        $user = Auth::user();
        $show_branch = $user && $user->hasRole('super-admin');

        return view('livewire.locations.index', [
            'locations' => $locations,
            'show_branch' => $show_branch,
        ]);
    }
}

