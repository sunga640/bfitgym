<?php

namespace App\Livewire\Organization\Branches;

use App\Models\Branch;
use App\Services\Branches\BranchSummaryService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Branches')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status_filter = '';

    public bool $show_deactivate_modal = false;
    public bool $show_activate_modal = false;
    public ?int $selected_branch_id = null;

    public function mount(): void
    {
        $this->authorize('viewAny', Branch::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function confirmDeactivate(int $branch_id): void
    {
        $branch = Branch::findOrFail($branch_id);
        $this->authorize('manageStatus', $branch);

        $this->selected_branch_id = $branch_id;
        $this->show_deactivate_modal = true;
    }

    public function confirmActivate(int $branch_id): void
    {
        $branch = Branch::findOrFail($branch_id);
        $this->authorize('manageStatus', $branch);

        $this->selected_branch_id = $branch_id;
        $this->show_activate_modal = true;
    }

    public function deactivateBranch(): void
    {
        if (!$this->selected_branch_id) {
            return;
        }

        $branch = Branch::findOrFail($this->selected_branch_id);
        $this->authorize('manageStatus', $branch);

        $branch->update(['status' => 'inactive']);

        $this->show_deactivate_modal = false;
        $this->selected_branch_id = null;

        session()->flash('success', __('Branch ":name" has been deactivated.', ['name' => $branch->name]));
    }

    public function activateBranch(): void
    {
        if (!$this->selected_branch_id) {
            return;
        }

        $branch = Branch::findOrFail($this->selected_branch_id);
        $this->authorize('manageStatus', $branch);

        $branch->update(['status' => 'active']);

        $this->show_activate_modal = false;
        $this->selected_branch_id = null;

        session()->flash('success', __('Branch ":name" has been activated.', ['name' => $branch->name]));
    }

    public function cancelModal(): void
    {
        $this->show_deactivate_modal = false;
        $this->show_activate_modal = false;
        $this->selected_branch_id = null;
    }

    public function render(): View
    {
        $user = Auth::user();

        // Build query based on user permissions
        $query = Branch::query();

        // HQ roles (can switch branches) see all branches
        // Branch users only see their own branch
        if (!$user->hasRole('super-admin') && !$user->hasPermissionTo('switch branches')) {
            $query->where('id', $user->branch_id);
        }

        // Apply filters
        $query->when($this->search, function ($q) {
            $q->where(function ($sub) {
                $sub->where('name', 'like', "%{$this->search}%")
                    ->orWhere('code', 'like', "%{$this->search}%")
                    ->orWhere('city', 'like', "%{$this->search}%");
            });
        })
        ->when($this->status_filter, fn($q) => $q->where('status', $this->status_filter))
        ->latest();

        $branches = $query->paginate(15);

        // Get metrics for visible branches (avoid N+1)
        $branch_ids = $branches->pluck('id');
        $metrics = app(BranchSummaryService::class)->getBranchRowMetrics($branch_ids);

        $is_hq_user = $user->hasRole('super-admin') || $user->hasPermissionTo('switch branches');

        return view('livewire.organization.branches.index', [
            'branches' => $branches,
            'metrics' => $metrics,
            'is_hq_user' => $is_hq_user,
        ]);
    }
}

