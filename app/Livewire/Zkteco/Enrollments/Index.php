<?php

namespace App\Livewire\Zkteco\Enrollments;

use App\Models\AccessControlDevice;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

#[Layout('components.layouts.app')]
#[Title('ZKTeco Enrollments')]
class Index extends \App\Livewire\AccessControl\Enrollments\Index
{
    public string $integration_type = AccessControlDevice::INTEGRATION_ZKTECO;
    public string $provider_filter = AccessControlDevice::PROVIDER_ZKTECO_AGENT;
    public string $integration_label = 'ZKTeco';
    public string $route_base = 'zkteco';
}
