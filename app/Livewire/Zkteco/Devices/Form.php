<?php

namespace App\Livewire\Zkteco\Devices;

use App\Models\AccessControlDevice;
use Livewire\Attributes\Layout;

#[Layout('components.layouts.app')]
class Form extends \App\Livewire\AccessControl\Devices\Form
{
    public string $integration_type = AccessControlDevice::INTEGRATION_ZKTECO;
    public string $integration_label = 'ZKTeco';
    public string $route_prefix = 'zkteco.devices';
    public string $route_base = 'zkteco';
    public string $device_model = 'ZKTeco Terminal';
    public string $provider = AccessControlDevice::PROVIDER_ZKBIO_PLATFORM;
}
