<?php

use App\Livewire\AccessControl\Devices\Index as HikvisionDevicesIndex;
use App\Livewire\Hikvision\Overview as HikvisionOverview;
use App\Livewire\Zkteco\Devices\Index as ZktecoDevicesIndex;
use App\Livewire\Zkteco\Overview as ZktecoOverview;
use App\Livewire\Zkteco\Settings as ZktecoSettings;
use App\Models\AccessControlDevice;
use App\Models\AccessIdentity;
use App\Models\AccessIntegrationConfig;
use App\Models\Branch;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->branch = Branch::factory()->create();

    $this->permissions = [
        'view attendance',
        'view access logs',
        'view access devices',
        'manage access devices',
        'manage access identities',
        'view hikvision',
        'manage hikvision',
        'view zkteco',
        'manage zkteco',
        'manage zkteco settings',
        'switch branches',
    ];

    foreach ($this->permissions as $permission_name) {
        Permission::firstOrCreate(['name' => $permission_name, 'guard_name' => 'web']);
    }
});

function integrationUser(Branch $branch): User
{
    return User::factory()->create([
        'branch_id' => $branch->id,
    ]);
}

function createAccessDevice(Branch $branch, string $name, string $integration_type, string $provider): AccessControlDevice
{
    return AccessControlDevice::query()->withoutBranchScope()->create([
        'branch_id' => $branch->id,
        'integration_type' => $integration_type,
        'provider' => $provider,
        'name' => $name,
        'device_model' => AccessControlDevice::MODEL_DS_K1T808MFWX,
        'device_type' => AccessControlDevice::TYPE_ENTRY,
        'serial_number' => strtoupper($integration_type) . '-' . strtoupper(str_replace(' ', '-', $name)) . '-' . uniqid(),
        'ip_address' => null,
        'status' => AccessControlDevice::STATUS_ACTIVE,
        'connection_status' => AccessControlDevice::CONNECTION_UNKNOWN,
        'auto_sync_enabled' => false,
        'sync_interval_minutes' => 5,
        'supports_face_recognition' => true,
        'supports_fingerprint' => true,
        'supports_card' => true,
    ]);
}

it('shows hikvision and zkteco main nav sections', function () {
    $super_admin_role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);

    $user = integrationUser($this->branch);
    $user->assignRole($super_admin_role);

    $this->actingAs($user);

    $response = $this->get(route('dashboard'));

    $response->assertOk();
    $response->assertSee('HIKVision');
    $response->assertSee('ZKTeco');
    $response->assertSee(route('hikvision.overview'));
    $response->assertSee(route('zkteco.overview'));
    $response->assertDontSee('href="' . route('attendance.index') . '"', false);
});

it('keeps legacy attendance and access routes redirecting to hikvision routes', function () {
    $super_admin_role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);

    $user = integrationUser($this->branch);
    $user->assignRole($super_admin_role);
    $this->actingAs($user);

    $device = createAccessDevice(
        $this->branch,
        'Legacy Device',
        AccessControlDevice::INTEGRATION_HIKVISION,
        AccessControlDevice::PROVIDER_HIKVISION_AGENT
    );

    $identity = AccessIdentity::query()->withoutBranchScope()->create([
        'branch_id' => $this->branch->id,
        'integration_type' => AccessControlDevice::INTEGRATION_HIKVISION,
        'provider' => AccessControlDevice::PROVIDER_HIKVISION_AGENT,
        'subject_type' => AccessIdentity::SUBJECT_MEMBER,
        'subject_id' => 1,
        'device_user_id' => 'LEGACY-001',
        'is_active' => true,
    ]);

    $this->get(route('attendance.index'))->assertRedirect('/hikvision/logs');
    $this->get(route('access-control.devices.index'))->assertRedirect('/hikvision/devices');
    $this->get(route('access-devices.index'))->assertRedirect('/hikvision/devices');
    $this->get(route('access-identities.index'))->assertRedirect('/hikvision/identities');
    $this->get(route('access-control.devices.show', $device))->assertRedirect(route('hikvision.devices.show', $device));
    $this->get(route('access-devices.edit', $device))->assertRedirect(route('hikvision.devices.edit', $device));
    $this->get(route('access-identities.edit', $identity))->assertRedirect(route('hikvision.identities.edit', $identity));
});

it('separates device listings by integration type', function () {
    $user = integrationUser($this->branch);
    $user->givePermissionTo(['view hikvision', 'view zkteco']);
    $this->actingAs($user);

    createAccessDevice(
        $this->branch,
        'HIK Device A',
        AccessControlDevice::INTEGRATION_HIKVISION,
        AccessControlDevice::PROVIDER_HIKVISION_AGENT
    );

    createAccessDevice(
        $this->branch,
        'ZK Device B',
        AccessControlDevice::INTEGRATION_ZKTECO,
        AccessControlDevice::PROVIDER_ZKBIO_PLATFORM
    );

    Livewire::test(HikvisionDevicesIndex::class)
        ->assertStatus(200)
        ->assertSee('HIK Device A')
        ->assertDontSee('ZK Device B');

    Livewire::test(ZktecoDevicesIndex::class)
        ->assertStatus(200)
        ->assertSee('ZK Device B')
        ->assertDontSee('HIK Device A');
});

it('keeps legacy attendance permission compatible for hikvision access', function () {
    $user = integrationUser($this->branch);
    $user->givePermissionTo(['view attendance']);
    $this->actingAs($user);

    Livewire::test(HikvisionOverview::class)
        ->assertStatus(200);
});

it('separates hikvision and zkteco permissions', function () {
    $hikvision_only_user = integrationUser($this->branch);
    $hikvision_only_user->givePermissionTo(['view hikvision']);

    $this->actingAs($hikvision_only_user);

    Livewire::test(HikvisionOverview::class)
        ->assertStatus(200);

    Livewire::test(ZktecoOverview::class)
        ->assertForbidden();
});

it('saves zkteco configuration mode and provider selection', function () {
    $user = integrationUser($this->branch);
    $user->givePermissionTo(['manage zkteco settings']);
    $this->actingAs($user);

    Livewire::test(ZktecoSettings::class)
        ->set('mode', AccessIntegrationConfig::MODE_PLATFORM)
        ->set('provider', AccessControlDevice::PROVIDER_ZKBIO_PLATFORM)
        ->set('is_enabled', true)
        ->set('sync_enabled', true)
        ->set('agent_fallback_enabled', false)
        ->set('platform_base_url', 'https://zkbio.example.com')
        ->set('platform_username', 'admin')
        ->set('platform_password', 'secret-pass')
        ->set('platform_site_code', 'MAIN-SITE')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('access_integration_configs', [
        'branch_id' => $this->branch->id,
        'integration_type' => AccessControlDevice::INTEGRATION_ZKTECO,
        'mode' => AccessIntegrationConfig::MODE_PLATFORM,
        'provider' => AccessControlDevice::PROVIDER_ZKBIO_PLATFORM,
    ]);

    Livewire::test(ZktecoSettings::class)
        ->set('mode', AccessIntegrationConfig::MODE_AGENT)
        ->assertSet('provider', AccessControlDevice::PROVIDER_ZKTECO_AGENT)
        ->set('agent_fallback_enabled', true)
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('access_integration_configs', [
        'branch_id' => $this->branch->id,
        'integration_type' => AccessControlDevice::INTEGRATION_ZKTECO,
        'mode' => AccessIntegrationConfig::MODE_AGENT,
        'provider' => AccessControlDevice::PROVIDER_ZKTECO_AGENT,
    ]);
});
