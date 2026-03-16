<?php

use App\Livewire\Hikvision\Overview as HikvisionOverview;
use App\Livewire\Zkteco\Overview as ZktecoOverview;
use App\Livewire\Zkteco\Settings as ZktecoSettings;
use App\Models\Branch;
use App\Models\User;
use App\Models\ZktecoConnection;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->branch = Branch::factory()->create();

    $permissions = [
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

    foreach ($permissions as $permission_name) {
        Permission::firstOrCreate(['name' => $permission_name, 'guard_name' => 'web']);
    }
});

function integrationUser(Branch $branch): User
{
    return User::factory()->create([
        'branch_id' => $branch->id,
    ]);
}

it('shows separate main nav sections for hikvision and zkteco', function () {
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

    $this->get(route('attendance.index'))->assertRedirect('/hikvision/logs');
    $this->get(route('access-control.devices.index'))->assertRedirect('/hikvision/devices');
    $this->get(route('access-devices.index'))->assertRedirect('/hikvision/devices');
    $this->get(route('access-identities.index'))->assertRedirect('/hikvision/identities');
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

it('saves zkteco connection settings into dedicated zkteco_connections table', function () {
    $user = integrationUser($this->branch);
    $user->givePermissionTo(['manage zkteco settings']);
    $this->actingAs($user);

    Livewire::test(ZktecoSettings::class)
        ->set('base_url', 'https://zkbio.example.com')
        ->set('port', 8443)
        ->set('username', 'admin')
        ->set('password', 'secret-pass')
        ->set('ssl_enabled', true)
        ->set('allow_self_signed', true)
        ->set('timeout_seconds', 15)
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('zkteco_connections', [
        'branch_id' => $this->branch->id,
        'provider' => ZktecoConnection::PROVIDER_ZKBIO_API,
        'base_url' => 'https://zkbio.example.com',
        'port' => 8443,
    ]);
});

