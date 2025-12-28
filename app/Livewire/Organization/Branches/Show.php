<?php

namespace App\Livewire\Organization\Branches;

use App\Models\Branch;
use App\Models\User;
use App\Services\BranchContext;
use App\Services\Branches\BranchSummaryService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Branch Details')]
class Show extends Component
{
    public Branch $branch;

    public string $active_tab = 'overview';

    public function mount(Branch $branch): void
    {
        $this->branch = $branch->load(['setting']);
        $this->authorize('view', $branch);
    }

    public function setTab(string $tab): void
    {
        $this->active_tab = $tab;
    }

    public function switchToBranch(): void
    {
        $this->authorize('switch', $this->branch);

        $branch_context = app(BranchContext::class);

        if ($branch_context->setCurrentBranch($this->branch->id)) {
            session()->flash('success', __('Switched to branch ":name".', ['name' => $this->branch->name]));
            $this->redirect(route('dashboard'), navigate: true);
        } else {
            session()->flash('error', __('Unable to switch to this branch.'));
        }
    }

    #[Computed]
    public function overview(): array
    {
        return app(BranchSummaryService::class)->getBranchOverview($this->branch->id);
    }

    #[Computed]
    public function upcomingSchedule(): array
    {
        $from = Carbon::now();
        $to = Carbon::now()->addDays(7);

        return app(BranchSummaryService::class)->getUpcomingSchedule($this->branch->id, $from, $to);
    }

    #[Computed]
    public function financeSummary(): array
    {
        return app(BranchSummaryService::class)->getFinanceSummary($this->branch->id);
    }

    #[Computed]
    public function operationsSummary(): array
    {
        return app(BranchSummaryService::class)->getOperationsSummary($this->branch->id);
    }

    #[Computed]
    public function staff(): \Illuminate\Database\Eloquent\Collection
    {
        return User::query()
            ->where('branch_id', $this->branch->id)
            ->with('roles')
            ->latest()
            ->get();
    }

    #[Computed]
    public function canSwitchToBranch(): bool
    {
        $user = Auth::user();
        $branch_context = app(BranchContext::class);

        return $branch_context->canSwitchBranches($user)
            && $this->branch->status === 'active'
            && $branch_context->getCurrentBranchId() !== $this->branch->id;
    }

    #[Computed]
    public function canEdit(): bool
    {
        return Auth::user()->can('update', $this->branch);
    }

    #[Computed]
    public function canManageStatus(): bool
    {
        return Auth::user()->can('manageStatus', $this->branch);
    }

    public function render(): View
    {
        return view('livewire.organization.branches.show', [
            'branch' => $this->branch,
        ]);
    }
}

