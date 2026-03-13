<?php

namespace App\Livewire\Zkteco\Agents;

use App\Models\AccessControlDevice;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

#[Layout('components.layouts.app', ['title' => 'ZKTeco Agents'])]
#[Title('ZKTeco Agents')]
class Index extends \App\Livewire\AccessControl\Agents\Index
{
    public string $integration_type = AccessControlDevice::INTEGRATION_ZKTECO;
    public string $provider_filter = AccessControlDevice::PROVIDER_ZKTECO_AGENT;
    public string $integration_label = 'ZKTeco';
    public string $route_prefix = 'zkteco.agents';
    public string $route_base = 'zkteco';
}
