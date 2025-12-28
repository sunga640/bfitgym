<?php

namespace App\Livewire\Insurers;

use App\Models\Insurer;
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
    public string $status_filter = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function deleteInsurer(int $insurer_id): void
    {
        try {
            $insurer = Insurer::findOrFail($insurer_id);

            $this->authorize('delete', $insurer);

            // Check if insurer has member insurances
            $insurance_count = $insurer->memberInsurances()->count();
            if ($insurance_count > 0) {
                session()->flash('error', __('Cannot delete insurer with :count active insurance policies. Remove the policies first.', ['count' => $insurance_count]));
                return;
            }

            $insurer_name = $insurer->name;

            DB::beginTransaction();
            $insurer->delete();
            DB::commit();

            Log::info('Insurer deleted', [
                'insurer_id' => $insurer_id,
                'insurer_name' => $insurer_name,
                'user_id' => auth()->id(),
            ]);

            session()->flash('success', __('Insurer ":name" deleted successfully.', ['name' => $insurer_name]));
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            session()->flash('error', __('You do not have permission to delete this insurer.'));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            session()->flash('error', __('Insurer not found.'));
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to delete insurer', [
                'insurer_id' => $insurer_id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            session()->flash('error', __('Failed to delete the insurer. Please try again.'));
        }
    }

    public function toggleStatus(int $insurer_id): void
    {
        try {
            $insurer = Insurer::findOrFail($insurer_id);

            $this->authorize('update', $insurer);

            $new_status = $insurer->status === 'active' ? 'inactive' : 'active';
            $insurer->update(['status' => $new_status]);

            Log::info('Insurer status changed', [
                'insurer_id' => $insurer->id,
                'insurer_name' => $insurer->name,
                'new_status' => $new_status,
                'user_id' => auth()->id(),
            ]);

            $status_label = $new_status === 'active' ? __('activated') : __('deactivated');
            session()->flash('success', __('Insurer ":name" :status successfully.', [
                'name' => $insurer->name,
                'status' => $status_label,
            ]));
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            session()->flash('error', __('You do not have permission to update this insurer.'));
        } catch (\Exception $e) {
            Log::error('Failed to toggle insurer status', [
                'insurer_id' => $insurer_id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            session()->flash('error', __('Failed to update the insurer status. Please try again.'));
        }
    }

    public function render(): View
    {
        $insurers = Insurer::query()
            ->withCount('memberInsurances')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', "%{$this->search}%")
                        ->orWhere('contact_person', 'like', "%{$this->search}%")
                        ->orWhere('phone', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%");
                });
            })
            ->when($this->status_filter, fn($query) => $query->where('status', $this->status_filter))
            ->latest()
            ->paginate(12);

        return view('livewire.insurers.index', [
            'insurers' => $insurers,
        ]);
    }
}

