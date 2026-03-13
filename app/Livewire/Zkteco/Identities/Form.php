<?php

namespace App\Livewire\Zkteco\Identities;

use App\Models\AccessControlDevice;
use Livewire\Attributes\Layout;

#[Layout('components.layouts.app')]
class Form extends \App\Livewire\AccessIdentities\Form
{
    public string $integration_type = AccessControlDevice::INTEGRATION_ZKTECO;
    public string $integration_label = 'ZKTeco';
    public string $route_prefix = 'zkteco.identities';
    public string $provider = AccessControlDevice::PROVIDER_ZKBIO_PLATFORM;
}
