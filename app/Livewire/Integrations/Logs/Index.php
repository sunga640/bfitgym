<?php

namespace App\Livewire\Integrations\Logs;

use App\Models\AccessControlDevice;
use App\Models\AccessLog;
use App\Services\BranchContext;
use App\Support\Integrations\IntegrationPermission;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Integration Logs')]
class Index extends Component
{
    use WithPagination;

    public string $integration_type = AccessControlDevice::INTEGRATION_HIKVISION;
    public string $integration_label = 'HIKVision';
    public string $route_base = 'hikvision';

    #[Url]
    public string $search = '';

    #[Url]
    public string $direction_filter = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingDirectionFilter(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        if (!IntegrationPermission::canView(auth()->user(), $this->integration_type)) {
            abort(403);
        }

        $branch_id = app(BranchContext::class)->getCurrentBranchId();

        $logs = AccessLog::query()
            ->with(['accessControlDevice:id,name', 'accessIdentity:id,device_user_id'])
            ->forIntegration($this->integration_type)
            ->when($branch_id, fn($q) => $q->where('branch_id', $branch_id))
            ->when($this->direction_filter, fn($q) => $q->where('direction', $this->direction_filter))
            ->when($this->search, function ($query) {
                $query->where(function ($inner) {
                    $inner->whereHas('accessControlDevice', fn($q) => $q->where('name', 'like', "%{$this->search}%"))
                        ->orWhereHas('accessIdentity', fn($q) => $q->where('device_user_id', 'like', "%{$this->search}%"))
                        ->orWhere('subject_id', 'like', "%{$this->search}%");
                });
            })
            ->latest('event_timestamp')
            ->paginate(20);

        return view('livewire.integrations.logs.index', [
            'logs' => $logs,
            'integration_label' => $this->integration_label,
            'route_base' => $this->route_base,
        ]);
    }
}

