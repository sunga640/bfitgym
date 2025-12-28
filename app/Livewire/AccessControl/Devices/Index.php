<?php

namespace App\Livewire\AccessControl\Devices;

use App\Models\AccessControlAgent;
use App\Models\AccessControlDevice;
use App\Services\BranchContext;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app', ['title' => 'Access Control Device Assignments'])]
#[Title('Access Control Device Assignments')]
class Index extends Component
{
    public bool $show_assign_modal = false;
    public ?int $assign_device_id = null;

    /** @var array<int, int|null> */
    public array $primary_agent_ids = [];

    /** @var array<int, array<int>> */
    public array $assigned_agent_ids = [];

    public function openAssignModal(int $device_id): void
    {
        $device = AccessControlDevice::findOrFail($device_id);
        $this->authorize('update', $device);

        $this->assign_device_id = $device->id;
        $this->assigned_agent_ids = [
            $device->id => $device->agents()->pluck('access_control_agents.id')->all(),
        ];
        $this->show_assign_modal = true;
    }

    public function saveAssignments(): void
    {
        if (!$this->assign_device_id) {
            return;
        }

        $device = AccessControlDevice::findOrFail($this->assign_device_id);
        $this->authorize('update', $device);

        $branch_id = $device->branch_id;
        $agent_ids = $this->assigned_agent_ids[$device->id] ?? [];
        $agent_ids = array_values(array_unique(array_map('intval', $agent_ids)));

        // Keep it branch-safe: only allow agents from same branch.
        $valid_agent_ids = AccessControlAgent::query()
            ->where('branch_id', $branch_id)
            ->whereIn('id', $agent_ids)
            ->pluck('id')
            ->all();

        $sync_payload = [];
        foreach ($valid_agent_ids as $id) {
            $sync_payload[$id] = ['branch_id' => $branch_id];
        }

        DB::transaction(function () use ($device, $sync_payload) {
            $device->agents()->sync($sync_payload);
        });

        session()->flash('success', __('Agent assignments updated.'));
        $this->closeAssignModal();
    }

    public function savePrimaryAgent(int $device_id): void
    {
        $device = AccessControlDevice::findOrFail($device_id);
        $this->authorize('update', $device);

        $branch_id = $device->branch_id;
        $agent_id = $this->primary_agent_ids[$device_id] ?? null;
        $agent_id = $agent_id ? (int) $agent_id : null;

        if ($agent_id) {
            $exists = AccessControlAgent::query()
                ->where('branch_id', $branch_id)
                ->where('id', $agent_id)
                ->exists();

            if (!$exists) {
                session()->flash('error', __('Invalid agent selection.'));
                return;
            }
        }

        $device->update(['access_control_agent_id' => $agent_id]);
        session()->flash('success', __('Primary agent updated.'));
    }

    public function closeAssignModal(): void
    {
        $this->show_assign_modal = false;
        $this->assign_device_id = null;
    }

    public function render(): View
    {
        $this->authorize('viewAny', AccessControlDevice::class);

        $branch_id = app(BranchContext::class)->getCurrentBranchId();
        $stale_minutes = (int) config('access_control.device_heartbeat_stale_minutes', 10);

        $devices = AccessControlDevice::query()
            ->with(['primaryAgent', 'agents'])
            ->when($branch_id, fn($q) => $q->where('branch_id', $branch_id))
            ->withCount([
                'deviceCommands as pending_commands_count' => fn($q) => $q->where('status', 'pending'),
                'deviceCommands as failed_commands_count' => fn($q) => $q->where('status', 'failed'),
            ])
            ->latest()
            ->get();

        $agents = AccessControlAgent::query()
            ->when($branch_id, fn($q) => $q->where('branch_id', $branch_id))
            ->latest()
            ->get(['id', 'name', 'uuid', 'status', 'last_seen_at']);

        foreach ($devices as $device) {
            if (!array_key_exists($device->id, $this->primary_agent_ids)) {
                $this->primary_agent_ids[$device->id] = $device->access_control_agent_id;
            }
        }

        return view('livewire.access-control.devices.index', [
            'devices' => $devices,
            'agents' => $agents,
            'stale_minutes' => $stale_minutes,
        ]);
    }
}
