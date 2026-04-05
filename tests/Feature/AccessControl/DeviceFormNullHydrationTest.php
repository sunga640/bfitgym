<?php

use App\Livewire\AccessControl\Devices\Form as DeviceForm;
use App\Models\AccessControlDevice;
use App\Models\Branch;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    Permission::firstOrCreate(['name' => 'manage hikvision', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'switch branches', 'guard_name' => 'web']);
});

it('renders hikvision device edit page when nullable connection fields are null', function () {
    $branch = Branch::factory()->create();

    $user = User::factory()->create([
        'branch_id' => $branch->id,
    ]);
    $user->givePermissionTo('manage hikvision');

    $device = AccessControlDevice::query()->create([
        'branch_id' => $branch->id,
        'integration_type' => AccessControlDevice::INTEGRATION_HIKVISION,
        'provider' => AccessControlDevice::PROVIDER_HIKVISION_AGENT,
        'name' => 'Front Door',
        'device_model' => AccessControlDevice::MODEL_DS_K1T808MFWX,
        'device_type' => AccessControlDevice::TYPE_ENTRY,
        'serial_number' => 'SN-HYD-0001',
        'ip_address' => null,
        'port' => 80,
        'username' => null,
        'status' => AccessControlDevice::STATUS_ACTIVE,
        'connection_status' => AccessControlDevice::CONNECTION_UNKNOWN,
        'auto_sync_enabled' => true,
        'sync_interval_minutes' => 5,
        'supports_face_recognition' => true,
        'supports_fingerprint' => true,
        'supports_card' => true,
    ]);

    $this->actingAs($user)
        ->get(route('hikvision.devices.edit', $device))
        ->assertOk();
});

it('normalizes nullable string fields while hydrating device edit form', function () {
    $branch = Branch::factory()->create();

    $user = User::factory()->create([
        'branch_id' => $branch->id,
    ]);
    $user->givePermissionTo('manage hikvision');

    $device = AccessControlDevice::query()->create([
        'branch_id' => $branch->id,
        'integration_type' => AccessControlDevice::INTEGRATION_HIKVISION,
        'provider' => AccessControlDevice::PROVIDER_HIKVISION_AGENT,
        'name' => 'Lobby Device',
        'device_model' => AccessControlDevice::MODEL_DS_K1T808MFWX,
        'device_type' => AccessControlDevice::TYPE_ENTRY,
        'serial_number' => 'SN-HYD-0002',
        'ip_address' => null,
        'port' => 80,
        'username' => null,
        'status' => AccessControlDevice::STATUS_ACTIVE,
        'connection_status' => AccessControlDevice::CONNECTION_UNKNOWN,
        'auto_sync_enabled' => true,
        'sync_interval_minutes' => 5,
        'supports_face_recognition' => true,
        'supports_fingerprint' => true,
        'supports_card' => true,
    ]);

    $this->actingAs($user);

    Livewire::test(DeviceForm::class, ['device' => $device])
        ->assertStatus(200)
        ->assertSet('ip_address', '')
        ->assertSet('username', '');
});
