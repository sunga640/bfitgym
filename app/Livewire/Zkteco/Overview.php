<?php

namespace App\Livewire\Zkteco;

use App\Models\AccessControlAgentEnrollment;
use App\Models\AccessControlDevice;
use App\Models\AccessIdentity;
use App\Models\AccessIntegrationConfig;
use App\Models\AccessLog;
use App\Services\BranchContext;
use App\Support\Integrations\IntegrationPermission;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('ZKTeco')]
class Overview extends Component
{
    public function render(): View
    {
        if (!IntegrationPermission::canView(auth()->user(), AccessControlDevice::INTEGRATION_ZKTECO)) {
            abort(403);
        }

        $branch_id = app(BranchContext::class)->getCurrentBranchId();

        $config = AccessIntegrationConfig::query()
            ->withoutBranchScope()
            ->when($branch_id, fn($q) => $q->where('branch_id', $branch_id))
            ->where('integration_type', AccessControlDevice::INTEGRATION_ZKTECO)
            ->first();

        $device_count = AccessControlDevice::query()
            ->forIntegration(AccessControlDevice::INTEGRATION_ZKTECO)
            ->when($branch_id, fn($q) => $q->where('branch_id', $branch_id))
            ->count();

        $identity_count = AccessIdentity::query()
            ->forIntegration(AccessControlDevice::INTEGRATION_ZKTECO)
            ->when($branch_id, fn($q) => $q->where('branch_id', $branch_id))
            ->count();

        $log_count_today = AccessLog::query()
            ->forIntegration(AccessControlDevice::INTEGRATION_ZKTECO)
            ->when($branch_id, fn($q) => $q->where('branch_id', $branch_id))
            ->whereDate('event_timestamp', today())
            ->count();

        $enrollment_count = AccessControlAgentEnrollment::query()
            ->forIntegration(AccessControlDevice::INTEGRATION_ZKTECO)
            ->when($branch_id, fn($q) => $q->where('branch_id', $branch_id))
            ->count();

        return view('livewire.zkteco.overview', [
            'config' => $config,
            'device_count' => $device_count,
            'identity_count' => $identity_count,
            'log_count_today' => $log_count_today,
            'enrollment_count' => $enrollment_count,
        ]);
    }
}

