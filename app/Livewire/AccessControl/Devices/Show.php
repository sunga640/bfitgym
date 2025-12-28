<?php

namespace App\Livewire\AccessControl\Devices;

use App\Models\AccessControlAgent;
use App\Models\AccessControlDevice;
use App\Models\AccessControlDeviceCommand;
use App\Services\AccessControl\AgentDeviceAssignmentService;
use App\Services\BranchContext;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Device Details')]
class Show extends Component
{
    use WithPagination;

    public AccessControlDevice $device;

    // Modals
    public bool $show_assign_modal = false;
    public bool $show_unassign_modal = false;
    public ?int $unassign_agent_id = null;

    /** @var array<int> */
    public array $selected_agent_ids = [];

    public function mount(AccessControlDevice $device): void
    {
        $this->authorize('view', $device);
        $this->device = $device;
    }

    public function openAssignModal(): void
    {
        $this->authorize('update', $this->device);
        $this->selected_agent_ids = $this->device->agents->pluck('id')->all();
        $this->show_assign_modal = true;
    }

    public function closeAssignModal(): void
    {
        $this->show_assign_modal = false;
        $this->selected_agent_ids = [];
    }

    public function saveAssignments(): void
    {
        $this->authorize('update', $this->device);

        $service = app(AgentDeviceAssignmentService::class);
        $agent_ids = array_values(array_unique(array_map('intval', $this->selected_agent_ids)));

        // Get each agent and assign the device
        foreach ($agent_ids as $agent_id) {
            $agent = AccessControlAgent::find($agent_id);
            if ($agent && $agent->branch_id === $this->device->branch_id) {
                $agent->devices()->syncWithoutDetaching([
                    $this->device->id => ['branch_id' => $this->device->branch_id],
                ]);
            }
        }

        // Remove agents not in the list
        $current_agent_ids = $this->device->agents->pluck('id')->all();
        $agents_to_remove = array_diff($current_agent_ids, $agent_ids);
        foreach ($agents_to_remove as $agent_id) {
            $agent = AccessControlAgent::find($agent_id);
            if ($agent) {
                $agent->devices()->detach($this->device->id);
            }
        }

        $this->device->refresh();
        session()->flash('success', __('Agent assignments updated.'));
        $this->closeAssignModal();
    }

    public function confirmUnassign(int $agent_id): void
    {
        $this->authorize('update', $this->device);
        $this->unassign_agent_id = $agent_id;
        $this->show_unassign_modal = true;
    }

    public function closeUnassignModal(): void
    {
        $this->show_unassign_modal = false;
        $this->unassign_agent_id = null;
    }

    public function unassignAgent(): void
    {
        if (!$this->unassign_agent_id) {
            return;
        }

        $this->authorize('update', $this->device);

        $agent = AccessControlAgent::find($this->unassign_agent_id);
        if ($agent) {
            $service = app(AgentDeviceAssignmentService::class);
            $service->unassignDevice($agent, $this->device->id, auth()->user());
        }

        $this->device->refresh();
        session()->flash('success', __('Agent unassigned from device.'));
        $this->closeUnassignModal();
    }

    public function setPrimaryAgent($agent_id): void
    {
        $this->authorize('update', $this->device);

        // Handle empty string as null
        $agent_id = $agent_id !== '' && $agent_id !== null ? (int) $agent_id : null;

        if ($agent_id) {
            $agent = AccessControlAgent::find($agent_id);
            if (!$agent || $agent->branch_id !== $this->device->branch_id) {
                session()->flash('error', __('Invalid agent selection.'));
                return;
            }
        }

        $this->device->update(['access_control_agent_id' => $agent_id]);
        $this->device->refresh();

        session()->flash('success', __('Primary agent updated.'));
    }

    public function render(): View
    {
        $branch_id = app(BranchContext::class)->getCurrentBranchId();

        // Available agents for assignment
        $available_agents = AccessControlAgent::query()
            ->where('branch_id', $this->device->branch_id)
            ->where('status', AccessControlAgent::STATUS_ACTIVE)
            ->orderBy('name')
            ->get();

        // Recent commands for this device
        $recent_commands = AccessControlDeviceCommand::query()
            ->where('access_control_device_id', $this->device->id)
            ->with('claimedByAgent')
            ->latest()
            ->take(20)
            ->get();

        return view('livewire.access-control.devices.show', [
            'available_agents' => $available_agents,
            'recent_commands' => $recent_commands,
        ]);
    }
}
