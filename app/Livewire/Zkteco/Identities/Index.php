<?php

namespace App\Livewire\Zkteco\Identities;

use App\Models\AccessControlDevice;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

#[Layout('components.layouts.app', ['title' => 'ZKTeco Identities'])]
#[Title('ZKTeco Identities')]
class Index extends \App\Livewire\AccessIdentities\Index
{
    public string $integration_type = AccessControlDevice::INTEGRATION_ZKTECO;
    public string $integration_label = 'ZKTeco';
    public string $route_prefix = 'zkteco.identities';
}
