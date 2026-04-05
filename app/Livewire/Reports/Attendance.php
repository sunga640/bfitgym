<?php

namespace App\Livewire\Reports;

use App\Models\AccessControlDevice;
use App\Models\CvSecurityEvent;
use App\Models\ZktecoDevice;
use App\Services\Attendance\AttendanceReportService;
use App\Services\BranchContext;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Attendance Report')]
class Attendance extends Component
{
    use WithPagination;

    #[Url]
    public string $date_from = '';

    #[Url]
    public string $date_to = '';

    #[Url]
    public string $branch_id = '';

    #[Url]
    public string $integration_type = '';

    #[Url]
    public string $provider = '';

    #[Url]
    public string $device = '';

    #[Url]
    public string $direction = '';

    #[Url]
    public string $search = '';

    public function mount(BranchContext $branch_context): void
    {
        if ($this->date_from === '' || $this->date_to === '') {
            $this->date_from = now()->startOfMonth()->format('Y-m-d');
            $this->date_to = now()->endOfMonth()->format('Y-m-d');
        }

        if ($this->branch_id === '' && $branch_context->getCurrentBranchId()) {
            $this->branch_id = (string) $branch_context->getCurrentBranchId();
        }
    }

    public function updatingDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatingDateTo(): void
    {
        $this->resetPage();
    }

    public function updatingBranchId(): void
    {
        $this->device = '';
        $this->resetPage();
    }

    public function updatingIntegrationType(): void
    {
        $this->provider = '';
        $this->device = '';
        $this->resetPage();
    }

    public function updatingProvider(): void
    {
        $this->resetPage();
    }

    public function updatingDevice(): void
    {
        $this->resetPage();
    }

    public function updatingDirection(): void
    {
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function clearFilters(BranchContext $branch_context): void
    {
        $this->reset(['integration_type', 'provider', 'device', 'direction', 'search']);

        $this->date_from = now()->startOfMonth()->format('Y-m-d');
        $this->date_to = now()->endOfMonth()->format('Y-m-d');
        $this->branch_id = (string) ($branch_context->getCurrentBranchId() ?? '');

        $this->resetPage();
    }

    public function render(AttendanceReportService $report_service, BranchContext $branch_context): View
    {
        if (!auth()->user()->hasAnyPermission(['view attendance reports', 'view reports'])) {
            abort(403);
        }

        $can_switch_branches = $branch_context->canSwitchBranches(auth()->user());
        $active_branch_id = $can_switch_branches
            ? $this->toNullableInt($this->branch_id)
            : $branch_context->getCurrentBranchId();

        if (!$can_switch_branches) {
            $this->branch_id = (string) ($active_branch_id ?? '');
        }

        $filters = [
            'date_from' => $this->date_from,
            'date_to' => $this->date_to,
            'branch_id' => $active_branch_id,
            'integration_type' => $this->integration_type,
            'provider' => $this->provider,
            'device' => $this->device,
            'direction' => $this->direction,
            'search' => $this->search,
        ];

        $events = $report_service->query($filters)
            ->orderByDesc('event_timestamp')
            ->paginate(25);

        $summary = $report_service->summary($filters);

        $branches = $branch_context->getAccessibleBranches(auth()->user())
            ->map(fn($branch) => [
                'id' => $branch->id,
                'name' => $branch->name,
            ])
            ->values();

        return view('livewire.reports.attendance', [
            'events' => $events,
            'summary' => $summary,
            'branches' => $branches,
            'can_switch_branches' => $can_switch_branches,
            'provider_options' => $this->providerOptions(),
            'device_options' => $this->deviceOptions($active_branch_id, $this->integration_type),
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function providerOptions(): array
    {
        return [
            '' => __('All Providers'),
            AccessControlDevice::PROVIDER_HIKVISION_AGENT => __('Hikvision Agent'),
            AccessControlDevice::PROVIDER_ZKTECO_ZKBIO => __('ZKTeco ZKBio CVAccess'),
            AccessControlDevice::PROVIDER_ZKTECO_AGENT => __('ZKTeco Agent Fallback'),
            AccessControlDevice::PROVIDER_ZKBIO_PLATFORM => __('ZKBio Platform (Legacy Alias)'),
        ];
    }

    /**
     * @return array<int, array{key:string,label:string}>
     */
    private function deviceOptions(?int $branch_id, string $integration_type): array
    {
        $options = [];

        if ($integration_type === '' || $integration_type === AccessControlDevice::INTEGRATION_HIKVISION) {
            $hikvision_devices = AccessControlDevice::query()
                ->withoutBranchScope()
                ->when($branch_id, fn($query) => $query->where('branch_id', $branch_id))
                ->forIntegration(AccessControlDevice::INTEGRATION_HIKVISION)
                ->orderBy('name')
                ->get(['id', 'name']);

            foreach ($hikvision_devices as $device) {
                $options[] = [
                    'key' => AccessControlDevice::INTEGRATION_HIKVISION . ':' . $device->id,
                    'label' => 'HIKVision - ' . $device->name,
                ];
            }
        }

        if ($integration_type === '' || $integration_type === AccessControlDevice::INTEGRATION_ZKTECO) {
            $zkteco_devices = ZktecoDevice::query()
                ->withoutBranchScope()
                ->when($branch_id, fn($query) => $query->where('branch_id', $branch_id))
                ->orderBy('remote_name')
                ->orderBy('remote_device_id')
                ->get(['id', 'remote_name', 'remote_device_id']);

            foreach ($zkteco_devices as $device) {
                $name = $device->remote_name ?: $device->remote_device_id;
                $options[] = [
                    'key' => AccessControlDevice::INTEGRATION_ZKTECO . ':' . $device->id,
                    'label' => 'ZKTeco - ' . $name,
                ];
            }

            $cvsecurity_devices = CvSecurityEvent::query()
                ->withoutBranchScope()
                ->join('cvsecurity_connections as c', 'c.id', '=', 'cvsecurity_events.cvsecurity_connection_id')
                ->when($branch_id, fn($query) => $query->where('cvsecurity_events.branch_id', $branch_id))
                ->whereNotNull('cvsecurity_events.device_id')
                ->where('cvsecurity_events.device_id', '!=', '')
                ->groupBy('cvsecurity_events.cvsecurity_connection_id', 'cvsecurity_events.device_id')
                ->orderBy('cvsecurity_events.device_id')
                ->select([
                    'cvsecurity_events.cvsecurity_connection_id',
                    'cvsecurity_events.device_id',
                    DB::raw('MAX(c.name) as connection_name'),
                ])
                ->get();

            foreach ($cvsecurity_devices as $device) {
                $key = AccessControlDevice::INTEGRATION_ZKTECO
                    . ':cvsecurity:'
                    . (string) $device->cvsecurity_connection_id
                    . ':'
                    . (string) $device->device_id;

                $label = 'ZKTeco Agent - ' . (string) $device->device_id;
                if (!empty($device->connection_name)) {
                    $label .= ' (' . (string) $device->connection_name . ')';
                }

                $options[] = [
                    'key' => $key,
                    'label' => $label,
                ];
            }
        }

        return $options;
    }

    private function toNullableInt(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && ctype_digit($value)) {
            return (int) $value;
        }

        return null;
    }
}
