<?php

namespace App\Livewire\AccessDevices;

use App\Models\AccessControlDevice;
use App\Models\Location;
use App\Services\AccessControl\AccessControlCommandService;
use App\Services\BranchContext;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app', ['title' => 'Access Control Devices'])]
#[Title('Access Control Devices')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status_filter = '';

    #[Url]
    public string $connection_filter = '';

    #[Url]
    public string $location_filter = '';

    // Modal state
    public bool $showDeleteModal = false;
    public bool $showTestModal = false;
    public ?int $selected_device_id = null;
    public string $test_result = '';
    public bool $test_loading = false;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingConnectionFilter(): void
    {
        $this->resetPage();
    }

    public function updatingLocationFilter(): void
    {
        $this->resetPage();
    }

    /**
     * Toggle device status (active/inactive).
     */
    public function toggleStatus(int $id): void
    {
        $device = AccessControlDevice::findOrFail($id);
        $this->authorize('update', $device);

        $new_status = $device->status === AccessControlDevice::STATUS_ACTIVE
            ? AccessControlDevice::STATUS_INACTIVE
            : AccessControlDevice::STATUS_ACTIVE;

        $device->update(['status' => $new_status]);

        $status_label = $new_status === AccessControlDevice::STATUS_ACTIVE ? __('activated') : __('deactivated');
        session()->flash('success', __('Device :status successfully.', ['status' => $status_label]));
    }

    /**
     * Open test connection modal.
     */
    public function openTestModal(int $id): void
    {
        $this->selected_device_id = $id;
        $this->test_result = '';
        $this->showTestModal = true;
    }

    /**
     * Test connection to device.
     */
    public function testConnection(): void
    {
        if (!$this->selected_device_id) {
            return;
        }

        $device = AccessControlDevice::findOrFail($this->selected_device_id);
        $this->authorize('testConnection', $device);

        $this->test_loading = true;

        try {
            // Cloud MUST NOT talk to LAN devices directly.
            // Enqueue a lightweight logs pull; the local agent will reach the device and update health/logs.
            app(AccessControlCommandService::class)->enqueueLogsPull($device, $device->logs_synced_until);

            $this->test_result = 'queued';
            session()->flash('success', __('Connection check enqueued. The local agent will verify connectivity and update status.'));
        } catch (\Exception $e) {
            $this->test_result = 'error';
            session()->flash('error', __('Failed to enqueue connection check: :message', ['message' => $e->getMessage()]));
            Log::error('Device connection test failed', [
                'device_id' => $this->selected_device_id,
                'error' => $e->getMessage(),
            ]);
        }

        $this->test_loading = false;
    }

    /**
     * Sync access logs from device.
     */
    public function syncDevice(int $id): void
    {
        $device = AccessControlDevice::findOrFail($id);
        $this->authorize('sync', $device);

        try {
            // Cloud MUST NOT poll devices. Enqueue a logs pull command for the agent.
            app(AccessControlCommandService::class)->enqueueLogsPull($device, $device->logs_synced_until);
            session()->flash('success', __('Logs pull enqueued. The local agent will sync logs and push them to the cloud.'));
        } catch (\Exception $e) {
            session()->flash('error', __('Failed to enqueue logs pull: :message', ['message' => $e->getMessage()]));
            Log::error('Device sync failed', [
                'device_id' => $id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Open delete confirmation modal.
     */
    public function confirmDelete(int $id): void
    {
        $this->selected_device_id = $id;
        $this->showDeleteModal = true;
    }

    /**
     * Delete device.
     */
    public function delete(): void
    {
        if (!$this->selected_device_id) {
            return;
        }

        $device = AccessControlDevice::findOrFail($this->selected_device_id);
        $this->authorize('delete', $device);

        try {
            // Check for access logs
            if ($device->accessLogs()->count() > 0) {
                session()->flash('error', __('Cannot delete device with existing access logs. Consider deactivating instead.'));
                $this->showDeleteModal = false;
                return;
            }

            $device->delete();
            session()->flash('success', __('Device deleted successfully.'));
        } catch (\Exception $e) {
            session()->flash('error', __('Failed to delete device. Please try again.'));
            Log::error('Failed to delete device', [
                'device_id' => $this->selected_device_id,
                'error' => $e->getMessage(),
            ]);
        }

        $this->showDeleteModal = false;
        $this->selected_device_id = null;
    }

    /**
     * Close modals.
     */
    public function closeModals(): void
    {
        $this->showDeleteModal = false;
        $this->showTestModal = false;
        $this->selected_device_id = null;
        $this->test_result = '';
    }

    public function render(): View
    {
        $this->authorize('viewAny', AccessControlDevice::class);

        $branch_id = app(BranchContext::class)->getCurrentBranchId();
        $stale_minutes = (int) config('access_control.device_heartbeat_stale_minutes', 10);

        $devices = AccessControlDevice::query()
            ->with(['location', 'branch'])
            ->when($branch_id, fn($q) => $q->where('branch_id', $branch_id))
            ->withCount([
                'deviceCommands as pending_commands_count' => fn($q) => $q->where('status', 'pending'),
                'deviceCommands as failed_commands_count' => fn($q) => $q->where('status', 'failed'),
            ])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', "%{$this->search}%")
                        ->orWhere('serial_number', 'like', "%{$this->search}%")
                        ->orWhere('ip_address', 'like', "%{$this->search}%");
                });
            })
            ->when($this->status_filter, fn($q) => $q->where('status', $this->status_filter))
            ->when($this->connection_filter, fn($q) => $q->where('connection_status', $this->connection_filter))
            ->when($this->location_filter, fn($q) => $q->where('location_id', $this->location_filter))
            ->latest()
            ->paginate(10);

        $locations = Location::query()
            ->when($branch_id, fn($q) => $q->where('branch_id', $branch_id))
            ->orderBy('name')
            ->get(['id', 'name']);

        // Stats for header
        $stats = [
            'total' => AccessControlDevice::when($branch_id, fn($q) => $q->where('branch_id', $branch_id))->count(),
            'online' => AccessControlDevice::when($branch_id, fn($q) => $q->where('branch_id', $branch_id))->online()->count(),
            'offline' => AccessControlDevice::when($branch_id, fn($q) => $q->where('branch_id', $branch_id))->offline()->count(),
        ];

        return view('livewire.access-devices.index', [
            'devices' => $devices,
            'locations' => $locations,
            'stats' => $stats,
            'stale_minutes' => $stale_minutes,
        ]);
    }
}

