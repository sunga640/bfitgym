<?php

namespace App\Livewire\Hikvision;

use App\Models\AccessControlAgent;
use App\Models\AccessControlDevice;
use App\Models\AccessIdentity;
use App\Models\AccessLog;
use App\Services\BranchContext;
use App\Support\Integrations\IntegrationPermission;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('HIKVision')]
class Overview extends Component
{
    public function render(): View
    {
        if (!IntegrationPermission::canView(auth()->user(), AccessControlDevice::INTEGRATION_HIKVISION)) {
            abort(403);
        }

        $branch_id = app(BranchContext::class)->getCurrentBranchId();

        $device_count = AccessControlDevice::query()
            ->forIntegration(AccessControlDevice::INTEGRATION_HIKVISION)
            ->when($branch_id, fn($q) => $q->where('branch_id', $branch_id))
            ->count();

        $agent_count = AccessControlAgent::query()
            ->when($branch_id, fn($q) => $q->where('branch_id', $branch_id))
            ->where(function ($query) {
                $query->whereNull('supported_providers')
                    ->orWhereJsonContains('supported_providers', AccessControlDevice::PROVIDER_HIKVISION_AGENT);
            })
            ->count();

        $identity_count = AccessIdentity::query()
            ->forIntegration(AccessControlDevice::INTEGRATION_HIKVISION)
            ->when($branch_id, fn($q) => $q->where('branch_id', $branch_id))
            ->count();

        $log_count_today = AccessLog::query()
            ->forIntegration(AccessControlDevice::INTEGRATION_HIKVISION)
            ->when($branch_id, fn($q) => $q->where('branch_id', $branch_id))
            ->whereDate('event_timestamp', today())
            ->count();

        return view('livewire.hikvision.overview', [
            'device_count' => $device_count,
            'agent_count' => $agent_count,
            'identity_count' => $identity_count,
            'log_count_today' => $log_count_today,
        ]);
    }
}

