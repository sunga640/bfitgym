<?php

namespace App\Livewire\AccessControl\Agents;

use App\Models\AccessControlAgent;
use App\Services\BranchContext;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app', ['title' => 'Access Control Agents'])]
#[Title('Access Control Agents')]
class Index extends Component
{
    use WithPagination;

    public bool $show_revoke_modal = false;
    public ?int $selected_agent_id = null;

    public function confirmRevoke(int $agent_id): void
    {
        $agent = AccessControlAgent::findOrFail($agent_id);
        $this->authorize('update', $agent);

        $this->selected_agent_id = $agent->id;
        $this->show_revoke_modal = true;
    }

    public function revoke(): void
    {
        if (!$this->selected_agent_id) {
            return;
        }

        $agent = AccessControlAgent::findOrFail($this->selected_agent_id);
        $this->authorize('update', $agent);

        $agent->update(['status' => AccessControlAgent::STATUS_REVOKED]);

        session()->flash('success', __('Agent revoked successfully.'));
        $this->closeModal();
    }

    public function closeModal(): void
    {
        $this->show_revoke_modal = false;
        $this->selected_agent_id = null;
    }

    public function render(): View
    {
        $this->authorize('viewAny', AccessControlAgent::class);

        $branch_id = app(BranchContext::class)->getCurrentBranchId();
        $stale_minutes = (int) config('access_control.agent_last_seen_stale_minutes', 10);

        $agents = AccessControlAgent::query()
            ->when($branch_id, fn($q) => $q->where('branch_id', $branch_id))
            ->latest()
            ->paginate(12);

        return view('livewire.access-control.agents.index', [
            'agents' => $agents,
            'stale_minutes' => $stale_minutes,
        ]);
    }
}
