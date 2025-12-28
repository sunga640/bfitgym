<?php

namespace App\Livewire\ClassTypes;

use App\Models\ClassType;
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

    #[Url]
    public string $booking_fee_filter = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingBookingFeeFilter(): void
    {
        $this->resetPage();
    }

    public function deleteClassType(int $class_type_id): void
    {
        try {
            $class_type = ClassType::findOrFail($class_type_id);

            $this->authorize('delete', $class_type);

            $class_type_name = $class_type->name;

            // Check if class type has sessions
            if ($class_type->sessions()->exists()) {
                session()->flash('error', __('Cannot delete class type ":name" because it has associated sessions.', ['name' => $class_type_name]));
                return;
            }

            DB::beginTransaction();
            $class_type->delete();
            DB::commit();

            Log::info('Class type deleted', [
                'class_type_id' => $class_type_id,
                'class_type_name' => $class_type_name,
                'branch_id' => $class_type->branch_id,
                'user_id' => auth()->id(),
            ]);

            session()->flash('success', __('Class type ":name" deleted successfully.', ['name' => $class_type_name]));
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            session()->flash('error', __('You do not have permission to delete this class type.'));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            session()->flash('error', __('Class type not found.'));
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to delete class type', [
                'class_type_id' => $class_type_id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            session()->flash('error', __('Failed to delete the class type. Please try again.'));
        }
    }

    public function render(): View
    {
        $class_types = ClassType::query()
            ->with(['branch', 'sessions'])
            ->withCount('sessions')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', "%{$this->search}%")
                        ->orWhere('description', 'like', "%{$this->search}%");
                });
            })
            ->when($this->status_filter, fn($query) => $query->where('status', $this->status_filter))
            ->when($this->booking_fee_filter !== '', fn($query) => $query->where('has_booking_fee', $this->booking_fee_filter === '1'))
            ->latest()
            ->paginate(12);

        $user = Auth::user();
        $show_branch = $user && $user->hasRole('super-admin');

        return view('livewire.class-types.index', [
            'class_types' => $class_types,
            'show_branch' => $show_branch,
        ]);
    }
}

