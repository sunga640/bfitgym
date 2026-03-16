<?php

namespace App\Livewire\Zkteco;

use App\Integrations\Zkteco\Services\ZktecoHealthService;
use App\Models\AccessControlDevice;
use App\Services\BranchContext;
use App\Support\Integrations\IntegrationPermission;
use App\Models\ZktecoAccessEvent;
use App\Models\ZktecoConnection;
use App\Models\ZktecoDevice;
use App\Models\ZktecoMemberMap;
use App\Models\ZktecoSyncRun;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('ZKTeco')]
class Overview extends Component
{
    public function render(ZktecoHealthService $health_service): View
    {
        if (!IntegrationPermission::canView(auth()->user(), AccessControlDevice::INTEGRATION_ZKTECO)) {
            abort(403);
        }

        $branch_id = app(BranchContext::class)->getCurrentBranchId();
        $connection = ZktecoConnection::query()
            ->withoutBranchScope()
            ->where('branch_id', $branch_id)
            ->first();

        $health = $health_service->healthForBranch($branch_id);

        $device_count = ZktecoDevice::query()
            ->withoutBranchScope()
            ->where('branch_id', $branch_id)
            ->count();

        $event_count_today = ZktecoAccessEvent::query()
            ->withoutBranchScope()
            ->where('branch_id', $branch_id)
            ->whereDate('occurred_at', today())
            ->count();

        $pending_biometrics = ZktecoMemberMap::query()
            ->withoutBranchScope()
            ->where('branch_id', $branch_id)
            ->where('biometric_status', ZktecoMemberMap::BIOMETRIC_PENDING)
            ->count();

        $recent_runs = ZktecoSyncRun::query()
            ->withoutBranchScope()
            ->where('branch_id', $branch_id)
            ->latest('started_at')
            ->limit(5)
            ->get();

        $mapped_devices = $connection?->branchMappings()
            ->withoutBranchScope()
            ->where('branch_id', $branch_id)
            ->where('is_active', true)
            ->count();

        return view('livewire.zkteco.overview', [
            'connection' => $connection,
            'health' => $health,
            'device_count' => $device_count,
            'event_count_today' => $event_count_today,
            'pending_biometrics' => $pending_biometrics,
            'mapped_devices' => $mapped_devices,
            'recent_runs' => $recent_runs,
        ]);
    }
}
