<?php

use App\Integrations\Zkteco\Services\ZktecoEventImportService;
use App\Livewire\Zkteco\Logs\Index as ZktecoLogsIndex;
use App\Models\Branch;
use App\Models\Member;
use App\Models\User;
use App\Models\ZktecoAccessEvent;
use App\Models\ZktecoConnection;
use App\Models\ZktecoDevice;
use App\Models\ZktecoMemberMap;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

it('deduplicates zkteco events by remote event id and fingerprint', function () {
    $branch = Branch::factory()->create();
    $member = Member::factory()->create(['branch_id' => $branch->id, 'status' => 'active']);

    $connection = ZktecoConnection::query()->withoutBranchScope()->create([
        'branch_id' => $branch->id,
        'provider' => ZktecoConnection::PROVIDER_ZKBIO_API,
        'status' => ZktecoConnection::STATUS_CONNECTED,
        'base_url' => 'https://zkbio.example.com',
        'api_key' => 'secret',
        'ssl_enabled' => true,
        'allow_self_signed' => true,
        'timeout_seconds' => 10,
    ]);

    ZktecoDevice::query()->withoutBranchScope()->create([
        'zkteco_connection_id' => $connection->id,
        'branch_id' => $branch->id,
        'remote_device_id' => 'DEV-001',
        'remote_name' => 'Main Gate',
        'is_online' => true,
        'is_assignable' => true,
    ]);

    ZktecoMemberMap::query()->withoutBranchScope()->create([
        'branch_id' => $branch->id,
        'zkteco_connection_id' => $connection->id,
        'member_id' => $member->id,
        'remote_personnel_id' => 'P-001',
        'remote_personnel_code' => $member->member_no,
        'biometric_status' => 'pending',
        'access_active' => true,
    ]);

    $payload = [
        'events' => [
            [
                'eventId' => 'EV-100',
                'deviceId' => 'DEV-001',
                'personnelId' => 'P-001',
                'direction' => 'in',
                'time' => now()->toIso8601String(),
                'status' => 'granted',
            ],
            [
                'eventId' => 'EV-100',
                'deviceId' => 'DEV-001',
                'personnelId' => 'P-001',
                'direction' => 'in',
                'time' => now()->toIso8601String(),
                'status' => 'granted',
            ],
            [
                'eventId' => 'EV-101',
                'deviceId' => 'DEV-001',
                'personnelId' => 'P-001',
                'direction' => 'out',
                'time' => now()->subMinute()->toIso8601String(),
                'status' => 'granted',
            ],
        ],
    ];

    Http::fake([
        '*api/v1/access/events*' => Http::response($payload, 200),
    ]);

    $first = app(ZktecoEventImportService::class)->syncBranch($connection);
    $second = app(ZktecoEventImportService::class)->syncBranch($connection->fresh());

    expect($first['imported'])->toBe(2);
    expect($first['skipped'])->toBe(1);
    expect($second['imported'])->toBe(0);

    $this->assertDatabaseCount('zkteco_access_events', 2);
    $this->assertDatabaseHas('zkteco_access_events', [
        'branch_id' => $branch->id,
        'remote_event_id' => 'EV-100',
        'matched_member' => true,
    ]);
});

it('scopes zkteco log view by branch and enforces sync permission', function () {
    Permission::firstOrCreate(['name' => 'view zkteco', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'manage zkteco', 'guard_name' => 'web']);

    $branch_a = Branch::factory()->create();
    $branch_b = Branch::factory()->create();

    $user = User::factory()->create([
        'branch_id' => $branch_a->id,
    ]);
    $user->givePermissionTo(['view zkteco']);

    ZktecoAccessEvent::query()->withoutBranchScope()->create([
        'branch_id' => $branch_a->id,
        'zkteco_connection_id' => ZktecoConnection::query()->withoutBranchScope()->create([
            'branch_id' => $branch_a->id,
            'provider' => ZktecoConnection::PROVIDER_ZKBIO_API,
            'status' => ZktecoConnection::STATUS_CONNECTED,
            'base_url' => 'https://zk-a.example.com',
            'api_key' => 'a',
            'ssl_enabled' => true,
            'allow_self_signed' => true,
            'timeout_seconds' => 10,
        ])->id,
        'remote_event_id' => 'BRANCH-A-EVENT',
        'event_fingerprint' => sha1('a'),
        'direction' => 'in',
        'occurred_at' => now(),
        'matched_member' => false,
    ]);

    ZktecoAccessEvent::query()->withoutBranchScope()->create([
        'branch_id' => $branch_b->id,
        'zkteco_connection_id' => ZktecoConnection::query()->withoutBranchScope()->create([
            'branch_id' => $branch_b->id,
            'provider' => ZktecoConnection::PROVIDER_ZKBIO_API,
            'status' => ZktecoConnection::STATUS_CONNECTED,
            'base_url' => 'https://zk-b.example.com',
            'api_key' => 'b',
            'ssl_enabled' => true,
            'allow_self_signed' => true,
            'timeout_seconds' => 10,
        ])->id,
        'remote_event_id' => 'BRANCH-B-EVENT',
        'event_fingerprint' => sha1('b'),
        'direction' => 'in',
        'occurred_at' => now(),
        'matched_member' => false,
    ]);

    $this->actingAs($user);

    Livewire::test(ZktecoLogsIndex::class)
        ->assertSee('BRANCH-A-EVENT')
        ->assertDontSee('BRANCH-B-EVENT')
        ->call('syncNow')
        ->assertForbidden();
});

