<?php

namespace App\Livewire\Zkteco\Agents;

use App\Models\AccessControlDevice;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

#[Layout('components.layouts.app')]
#[Title('ZKTeco Agent Details')]
class Show extends \App\Livewire\AccessControl\Agents\Show
{
    public string $integration_type = AccessControlDevice::INTEGRATION_ZKTECO;
    public string $provider_filter = AccessControlDevice::PROVIDER_ZKTECO_ZKBIO;
    public string $integration_label = 'ZKTeco';
    public string $route_base = 'zkteco';
}
