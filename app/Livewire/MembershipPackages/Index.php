<?php

namespace App\Livewire\MembershipPackages;

use App\Models\MembershipPackage;
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

    #[Url]
    public string $duration_type_filter = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingDurationTypeFilter(): void
    {
        $this->resetPage();
    }

    /**
     * Delete a membership package.
     */
    public function deletePackage(int $package_id): void
    {
        try {
            $package = MembershipPackage::findOrFail($package_id);

            $this->authorize('delete', $package);

            // Check if package has any subscriptions (active or not)
            $subscription_count = $package->subscriptions()->count();
            if ($subscription_count > 0) {
                $active_count = $package->subscriptions()->where('status', 'active')->count();

                if ($active_count > 0) {
                    session()->flash('error', __('Cannot delete package with :count active subscription(s). Please cancel or expire them first.', ['count' => $active_count]));
                    return;
                }

                // Has inactive/expired subscriptions - still block deletion for data integrity
                session()->flash('error', __('Cannot delete package with :count subscription record(s). Consider deactivating the package instead.', ['count' => $subscription_count]));
                return;
            }

            $package_name = $package->name;

            DB::beginTransaction();
            $package->delete();
            DB::commit();

            Log::info('Membership package deleted', [
                'package_id' => $package_id,
                'package_name' => $package_name,
                'branch_id' => current_branch_id(),
                'user_id' => auth()->id(),
            ]);

            session()->flash('success', __('Membership package ":name" deleted successfully.', ['name' => $package_name]));
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            session()->flash('error', __('You do not have permission to delete this package.'));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            session()->flash('error', __('Package not found.'));
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to delete membership package', [
                'package_id' => $package_id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            session()->flash('error', __('Failed to delete the package. Please try again.'));
        }
    }

    /**
     * Toggle the status of a membership package.
     */
    public function toggleStatus(int $package_id): void
    {
        try {
            $package = MembershipPackage::findOrFail($package_id);

            $this->authorize('update', $package);

            $old_status = $package->status;
            $new_status = $package->status === 'active' ? 'inactive' : 'active';

            // If deactivating, check for active subscriptions and warn
            if ($new_status === 'inactive') {
                $active_subscriptions = $package->subscriptions()->where('status', 'active')->count();
                if ($active_subscriptions > 0) {
                    // Allow deactivation but warn - existing subscriptions will continue but no new ones can be created
                    session()->flash('warning', __('Package deactivated. Note: :count existing active subscription(s) will continue until expiry.', ['count' => $active_subscriptions]));
                }
            }

            $package->update(['status' => $new_status]);

            Log::info('Membership package status changed', [
                'package_id' => $package->id,
                'package_name' => $package->name,
                'old_status' => $old_status,
                'new_status' => $new_status,
                'branch_id' => $package->branch_id,
                'user_id' => auth()->id(),
            ]);

            if (!session()->has('warning')) {
                $status_label = $new_status === 'active' ? __('activated') : __('deactivated');
                session()->flash('success', __('Package ":name" :status successfully.', [
                    'name' => $package->name,
                    'status' => $status_label,
                ]));
            }
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            session()->flash('error', __('You do not have permission to update this package.'));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            session()->flash('error', __('Package not found.'));
        } catch (\Exception $e) {
            Log::error('Failed to toggle membership package status', [
                'package_id' => $package_id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            session()->flash('error', __('Failed to update the package status. Please try again.'));
        }
    }

    /**
     * Clear all filters.
     */
    public function clearFilters(): void
    {
        $this->search = '';
        $this->status_filter = '';
        $this->duration_type_filter = '';
        $this->resetPage();
    }

    public function render(): View
    {
        $packages = MembershipPackage::query()
            ->withCount(['subscriptions', 'subscriptions as active_subscriptions_count' => function ($query) {
                $query->where('status', 'active');
            }])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', "%{$this->search}%")
                        ->orWhere('description', 'like', "%{$this->search}%");
                });
            })
            ->when($this->status_filter, function ($query) {
                $query->where('status', $this->status_filter);
            })
            ->when($this->duration_type_filter, function ($query) {
                $query->where('duration_type', $this->duration_type_filter);
            })
            ->latest()
            ->paginate(12);

        return view('livewire.membership-packages.index', [
            'packages' => $packages,
            'duration_types' => ['days', 'weeks', 'months', 'years'],
        ]);
    }
}
