<?php

namespace App\Livewire\Zkteco\Logs;

use App\Models\AccessControlDevice;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

#[Layout('components.layouts.app')]
#[Title('ZKTeco Logs')]
class Index extends \App\Livewire\Integrations\Logs\Index
{
    public string $integration_type = AccessControlDevice::INTEGRATION_ZKTECO;
    public string $integration_label = 'ZKTeco';
    public string $route_base = 'zkteco';
}
