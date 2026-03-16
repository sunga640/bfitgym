<?php

namespace App\Livewire\Zkteco\Logs;

use App\Integrations\Zkteco\Repositories\ZktecoConnectionRepository;
use App\Integrations\Zkteco\Services\ZktecoEventImportService;
use App\Models\AccessControlDevice;
use App\Models\ZktecoAccessEvent;
use App\Services\BranchContext;
use App\Support\Integrations\IntegrationPermission;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('ZKTeco Events')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $direction_filter = '';

    #[Url]
    public string $status_filter = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingDirectionFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function syncNow(
        BranchContext $branch_context,
        ZktecoConnectionRepository $connections,
        ZktecoEventImportService $event_import
    ): void {
        if (!IntegrationPermission::canManage(auth()->user(), AccessControlDevice::INTEGRATION_ZKTECO)) {
            abort(403);
        }

        $branch_id = $branch_context->getCurrentBranchId();
        if (!$branch_id) {
            session()->flash('error', __('Please select a branch first.'));
            return;
        }

        $connection = $connections->forBranch($branch_id);
        if (!$connection) {
            session()->flash('error', __('Configure the ZKTeco connection first.'));
            return;
        }

        try {
            $result = $event_import->syncBranch($connection, actor: auth()->user());

            session()->flash(
                'success',
                __('Event sync completed. Imported: :imported, Skipped: :skipped, Failed: :failed.', [
                    'imported' => $result['imported'],
                    'skipped' => $result['skipped'],
                    'failed' => $result['failed'],
                ])
            );
        } catch (\Throwable $e) {
            session()->flash('error', __('Failed to sync events: :message', ['message' => $e->getMessage()]));
        }
    }

    public function render(BranchContext $branch_context): View
    {
        if (!IntegrationPermission::canView(auth()->user(), AccessControlDevice::INTEGRATION_ZKTECO)) {
            abort(403);
        }

        $branch_id = $branch_context->getCurrentBranchId();

        $events = ZktecoAccessEvent::query()
            ->with(['device:id,remote_name,remote_device_id', 'member:id,first_name,last_name,member_no'])
            ->withoutBranchScope()
            ->when($branch_id, fn($query) => $query->where('branch_id', $branch_id))
            ->when($this->direction_filter !== '', fn($query) => $query->where('direction', $this->direction_filter))
            ->when($this->status_filter !== '', fn($query) => $query->where('event_status', $this->status_filter))
            ->when($this->search !== '', function ($query) {
                $query->where(function ($inner) {
                    $inner->where('remote_event_id', 'like', '%' . $this->search . '%')
                        ->orWhere('remote_personnel_id', 'like', '%' . $this->search . '%')
                        ->orWhereHas('member', function ($member_query) {
                            $member_query->where('member_no', 'like', '%' . $this->search . '%')
                                ->orWhere('first_name', 'like', '%' . $this->search . '%')
                                ->orWhere('last_name', 'like', '%' . $this->search . '%');
                        })
                        ->orWhereHas('device', function ($device_query) {
                            $device_query->where('remote_name', 'like', '%' . $this->search . '%')
                                ->orWhere('remote_device_id', 'like', '%' . $this->search . '%');
                        });
                });
            })
            ->latest('occurred_at')
            ->paginate(20);

        $available_statuses = ZktecoAccessEvent::query()
            ->withoutBranchScope()
            ->when($branch_id, fn($query) => $query->where('branch_id', $branch_id))
            ->whereNotNull('event_status')
            ->distinct()
            ->orderBy('event_status')
            ->pluck('event_status');

        return view('livewire.zkteco.logs.index', [
            'events' => $events,
            'available_statuses' => $available_statuses,
            'can_sync' => IntegrationPermission::canManage(auth()->user(), AccessControlDevice::INTEGRATION_ZKTECO),
        ]);
    }
}
