<?php

namespace App\Livewire\Zkteco\Devices;

use App\Models\AccessControlDevice;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

#[Layout('components.layouts.app')]
#[Title('ZKTeco Device Details')]
class Show extends \App\Livewire\AccessControl\Devices\Show
{
    public string $integration_type = AccessControlDevice::INTEGRATION_ZKTECO;
    public string $integration_label = 'ZKTeco';
    public string $route_prefix = 'zkteco.devices';
    public string $route_base = 'zkteco';
}
