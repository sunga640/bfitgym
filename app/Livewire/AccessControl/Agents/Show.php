<?php

namespace App\Livewire\AccessControl\Agents;

use App\Models\AccessControlAgent;
use App\Models\AccessControlAgentEnrollment;
use App\Models\AccessControlDevice;
use App\Models\AccessControlDeviceCommand;
use App\Services\AccessControl\AgentDeviceAssignmentService;
use App\Support\Integrations\IntegrationPermission;
use App\Support\AccessLogger;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Agent Details')]
class Show extends Component
{
    use WithPagination;

    public string $integration_type = AccessControlDevice::INTEGRATION_HIKVISION;
    public string $provider_filter = AccessControlDevice::PROVIDER_HIKVISION_AGENT;
    public string $integration_label = 'HIKVision';
    public string $route_base = 'hikvision';

    public AccessControlAgent $agent;

    // Modals
    public bool $show_assign_modal = false;
    public bool $show_unassign_modal = false;
    public bool $show_revoke_modal = false;
    public bool $show_delete_modal = false;
    public ?int $unassign_device_id = null;

    /** @var array<int> */
    public array $selected_device_ids = [];

    public function mount(AccessControlAgent $agent): void
    {
        if (!IntegrationPermission::canView(auth()->user(), $this->integration_type)) {
            abort(403);
        }

        if (!$this->supportsCurrentProvider($agent)) {
            abort(404);
        }

        $this->authorize('view', $agent);
        $this->agent = $agent;
    }

    // -------------------------------------------------------------------------
    // Device Assignment
    // -------------------------------------------------------------------------

    public function openAssignModal(): void
    {
        $this->authorize('update', $this->agent);
        $this->selected_device_ids = $this->agent->devices->pluck('id')->all();
        $this->show_assign_modal = true;
    }

    public function closeAssignModal(): void
    {
        $this->show_assign_modal = false;
        $this->selected_device_ids = [];
    }

    public function saveDeviceAssignments(): void
    {
        $this->authorize('update', $this->agent);

        $service = app(AgentDeviceAssignmentService::class);
        $device_ids = array_values(array_unique(array_map('intval', $this->selected_device_ids)));

        $service->assignDevices($this->agent, $device_ids, auth()->user(), sync: true);

        $this->agent->refresh();
        session()->flash('success', __('Device assignments updated.'));
        $this->closeAssignModal();
    }

    public function confirmUnassignDevice(int $device_id): void
    {
        $this->authorize('update', $this->agent);
        $this->unassign_device_id = $device_id;
        $this->show_unassign_modal = true;
    }

    public function closeUnassignModal(): void
    {
        $this->show_unassign_modal = false;
        $this->unassign_device_id = null;
    }

    public function unassignDevice(): void
    {
        if (!$this->unassign_device_id) {
            return;
        }

        $this->authorize('update', $this->agent);

        $service = app(AgentDeviceAssignmentService::class);
        $service->unassignDevice($this->agent, $this->unassign_device_id, auth()->user());

        $this->agent->refresh();
        session()->flash('success', __('Device unassigned from agent.'));
        $this->closeUnassignModal();
    }

    // -------------------------------------------------------------------------
    // Revoke Token
    // -------------------------------------------------------------------------

    public function confirmRevoke(): void
    {
        $this->authorize('update', $this->agent);
        $this->show_revoke_modal = true;
    }

    public function closeRevokeModal(): void
    {
        $this->show_revoke_modal = false;
    }

    public function revokeToken(): void
    {
        $this->authorize('update', $this->agent);

        $this->agent->revoke();

        app(AccessLogger::class)->info('agent_token_revoked', [
            'agent_id' => $this->agent->id,
            'agent_uuid' => $this->agent->uuid,
            'branch_id' => $this->agent->branch_id,
            'actor_user_id' => auth()->id(),
        ]);

        $this->agent->refresh();
        session()->flash('success', __('Agent token revoked. The agent will no longer be able to authenticate.'));
        $this->closeRevokeModal();
    }

    // -------------------------------------------------------------------------
    // Delete Agent
    // -------------------------------------------------------------------------

    public function confirmDelete(): void
    {
        $this->authorize('delete', $this->agent);
        $this->show_delete_modal = true;
    }

    public function closeDeleteModal(): void
    {
        $this->show_delete_modal = false;
    }

    public function deleteAgent(): void
    {
        $this->authorize('delete', $this->agent);

        $agent_id = $this->agent->id;
        $agent_uuid = $this->agent->uuid;
        $branch_id = $this->agent->branch_id;

        // Clear primary agent references on devices
        AccessControlDevice::query()
            ->where('access_control_agent_id', $this->agent->id)
            ->update(['access_control_agent_id' => null]);

        // Detach all devices
        $this->agent->devices()->detach();

        // Soft delete the agent
        $this->agent->delete();

        app(AccessLogger::class)->info('agent_deleted', [
            'agent_id' => $agent_id,
            'agent_uuid' => $agent_uuid,
            'branch_id' => $branch_id,
            'actor_user_id' => auth()->id(),
        ]);

        session()->flash('success', __('Agent deleted successfully.'));
        $this->redirect(route($this->route_base . '.agents.index'), navigate: true);
    }

    // -------------------------------------------------------------------------
    // Render
    // -------------------------------------------------------------------------

    public function render(): View
    {
        // Available devices for assignment
        $available_devices = AccessControlDevice::query()
            ->where('branch_id', $this->agent->branch_id)
            ->forIntegration($this->integration_type)
            ->when($this->provider_filter, fn($q) => $q->forProvider($this->provider_filter))
            ->where('status', AccessControlDevice::STATUS_ACTIVE)
            ->orderBy('name')
            ->get();

        // Recent enrollments for this agent
        $enrollments = AccessControlAgentEnrollment::query()
            ->forIntegration($this->integration_type)
            ->forProvider($this->provider_filter)
            ->where(function ($q) {
                $q->where('access_control_agent_id', $this->agent->id)
                    ->orWhere('used_by_agent_id', $this->agent->id);
            })
            ->with('createdBy')
            ->latest()
            ->take(10)
            ->get();

        // Recent commands claimed by this agent
        $recent_commands = AccessControlDeviceCommand::query()
            ->where('claimed_by_agent_id', $this->agent->id)
            ->forIntegration($this->integration_type)
            ->forProvider($this->provider_filter)
            ->with('device')
            ->latest()
            ->take(50)
            ->get();

        // Aggregate command stats
        $command_stats = [
            'total' => $recent_commands->count(),
            'done' => $recent_commands->where('status', 'done')->count(),
            'failed' => $recent_commands->where('status', 'failed')->count(),
            'pending' => $recent_commands->whereIn('status', ['pending', 'claimed', 'processing'])->count(),
        ];

        return view('livewire.access-control.agents.show', [
            'available_devices' => $available_devices,
            'enrollments' => $enrollments,
            'recent_commands' => $recent_commands,
            'command_stats' => $command_stats,
            'integration_label' => $this->integration_label,
            'route_base' => $this->route_base,
        ]);
    }

    protected function supportsCurrentProvider(AccessControlAgent $agent): bool
    {
        if ($this->provider_filter === AccessControlDevice::PROVIDER_HIKVISION_AGENT) {
            if (empty($agent->supported_providers)) {
                return true;
            }
        }

        return $agent->supportsProvider($this->provider_filter);
    }
}
