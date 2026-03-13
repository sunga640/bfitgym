<?php

namespace App\Livewire\AccessControl\Agents;

use App\Models\AccessControlAgent;
use App\Models\AccessControlDevice;
use App\Services\BranchContext;
use App\Support\Integrations\IntegrationPermission;
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

    public string $integration_type = AccessControlDevice::INTEGRATION_HIKVISION;
    public string $provider_filter = AccessControlDevice::PROVIDER_HIKVISION_AGENT;
    public string $integration_label = 'HIKVision';
    public string $route_prefix = 'hikvision.agents';
    public string $route_base = 'hikvision';

    public bool $show_revoke_modal = false;
    public ?int $selected_agent_id = null;

    public function confirmRevoke(int $agent_id): void
    {
        $agent = $this->scopedAgent($agent_id);
        $this->authorize('update', $agent);

        $this->selected_agent_id = $agent->id;
        $this->show_revoke_modal = true;
    }

    public function revoke(): void
    {
        if (!$this->selected_agent_id) {
            return;
        }

        $agent = $this->scopedAgent($this->selected_agent_id);
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
        if (!IntegrationPermission::canView(auth()->user(), $this->integration_type)) {
            abort(403);
        }

        $this->authorize('viewAny', AccessControlAgent::class);

        $branch_id = app(BranchContext::class)->getCurrentBranchId();
        $stale_minutes = (int) config('access_control.agent_last_seen_stale_minutes', 10);

        $agents = AccessControlAgent::query()
            ->when($branch_id, fn($q) => $q->where('branch_id', $branch_id))
            ->when(
                $this->provider_filter === AccessControlDevice::PROVIDER_HIKVISION_AGENT,
                fn($q) => $q->where(function ($inner) {
                    $inner->whereNull('supported_providers')
                        ->orWhereJsonContains('supported_providers', AccessControlDevice::PROVIDER_HIKVISION_AGENT);
                }),
                fn($q) => $q->whereJsonContains('supported_providers', $this->provider_filter),
            )
            ->latest()
            ->paginate(12);

        return view('livewire.access-control.agents.index', [
            'agents' => $agents,
            'stale_minutes' => $stale_minutes,
            'integration_label' => $this->integration_label,
            'route_prefix' => $this->route_prefix,
            'route_base' => $this->route_base,
        ]);
    }

    protected function scopedAgent(int $agent_id): AccessControlAgent
    {
        return AccessControlAgent::query()
            ->when(
                $this->provider_filter === AccessControlDevice::PROVIDER_HIKVISION_AGENT,
                fn($q) => $q->where(function ($inner) {
                    $inner->whereNull('supported_providers')
                        ->orWhereJsonContains('supported_providers', AccessControlDevice::PROVIDER_HIKVISION_AGENT);
                }),
                fn($q) => $q->whereJsonContains('supported_providers', $this->provider_filter),
            )
            ->findOrFail($agent_id);
    }
}
