<?php

namespace App\Livewire\Hikvision\Logs;

use App\Models\AccessControlDevice;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

#[Layout('components.layouts.app')]
#[Title('HIKVision Logs')]
class Index extends \App\Livewire\Integrations\Logs\Index
{
    public string $integration_type = AccessControlDevice::INTEGRATION_HIKVISION;
    public string $integration_label = 'HIKVision';
    public string $route_base = 'hikvision';
}
