<?php

use App\Livewire\Reports\Attendance as AttendanceReport;
use App\Models\AccessControlDevice;
use App\Models\AccessIdentity;
use App\Models\AccessLog;
use App\Models\Branch;
use App\Models\Member;
use App\Models\User;
use App\Models\ZktecoAccessEvent;
use App\Models\ZktecoConnection;
use App\Models\ZktecoDevice;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    Permission::firstOrCreate(['name' => 'view attendance reports', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'view reports', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'switch branches', 'guard_name' => 'web']);

    $this->branch = Branch::factory()->create();

    $this->user = User::factory()->create([
        'branch_id' => $this->branch->id,
    ]);

    $this->user->givePermissionTo(['view attendance reports']);

    $this->actingAs($this->user);
});

it('renders unified attendance report with hikvision and zkteco records', function () {
    $member = Member::factory()->create([
        'branch_id' => $this->branch->id,
    ]);

    $hikvision_device = AccessControlDevice::query()->create([
        'branch_id' => $this->branch->id,
        'integration_type' => AccessControlDevice::INTEGRATION_HIKVISION,
        'provider' => AccessControlDevice::PROVIDER_HIKVISION_AGENT,
        'name' => 'HIK Entry A',
        'device_model' => AccessControlDevice::MODEL_DS_K1T808MFWX,
        'device_type' => AccessControlDevice::TYPE_ENTRY,
        'serial_number' => 'HIK-001',
        'status' => AccessControlDevice::STATUS_ACTIVE,
    ]);

    $identity = AccessIdentity::query()->create([
        'branch_id' => $this->branch->id,
        'integration_type' => AccessControlDevice::INTEGRATION_HIKVISION,
        'provider' => AccessControlDevice::PROVIDER_HIKVISION_AGENT,
        'subject_type' => AccessIdentity::SUBJECT_MEMBER,
        'subject_id' => $member->id,
        'device_user_id' => 'MBR-HIK-1',
        'is_active' => true,
    ]);

    AccessLog::query()->create([
        'branch_id' => $this->branch->id,
        'integration_type' => AccessControlDevice::INTEGRATION_HIKVISION,
        'provider' => AccessControlDevice::PROVIDER_HIKVISION_AGENT,
        'access_control_device_id' => $hikvision_device->id,
        'access_identity_id' => $identity->id,
        'device_event_uid' => 'hik-event-1001',
        'subject_type' => AccessIdentity::SUBJECT_MEMBER,
        'subject_id' => $member->id,
        'direction' => AccessLog::DIRECTION_IN,
        'event_timestamp' => now()->subMinutes(30),
        'raw_payload' => ['source' => 'hikvision'],
    ]);

    $connection = ZktecoConnection::query()->withoutBranchScope()->create([
        'branch_id' => $this->branch->id,
        'provider' => ZktecoConnection::PROVIDER_ZKBIO_API,
        'status' => ZktecoConnection::STATUS_CONNECTED,
        'base_url' => 'http://127.0.0.1:4370',
    ]);

    $zkteco_device = ZktecoDevice::query()->withoutBranchScope()->create([
        'zkteco_connection_id' => $connection->id,
        'branch_id' => $this->branch->id,
        'remote_device_id' => 'ZK-REMOTE-1',
        'remote_name' => 'ZK Gate 1',
    ]);

    ZktecoAccessEvent::query()->withoutBranchScope()->create([
        'branch_id' => $this->branch->id,
        'zkteco_connection_id' => $connection->id,
        'zkteco_device_id' => $zkteco_device->id,
        'member_id' => $member->id,
        'remote_event_id' => 'zk-event-2001',
        'event_fingerprint' => sha1('zk-event-2001'),
        'remote_personnel_id' => 'MBR-ZK-1',
        'direction' => ZktecoAccessEvent::DIRECTION_IN,
        'occurred_at' => now()->subMinutes(20),
        'matched_member' => true,
        'raw_payload' => ['source' => 'zkteco'],
    ]);

    Livewire::test(AttendanceReport::class)
        ->set('date_from', now()->subDay()->format('Y-m-d'))
        ->set('date_to', now()->format('Y-m-d'))
        ->assertSee('HIK Entry A')
        ->assertSee('ZK Gate 1')
        ->assertSee('hik-event-1001')
        ->assertSee('zk-event-2001');
});

it('filters attendance report by integration type', function () {
    $member = Member::factory()->create([
        'branch_id' => $this->branch->id,
    ]);

    $hikvision_device = AccessControlDevice::query()->create([
        'branch_id' => $this->branch->id,
        'integration_type' => AccessControlDevice::INTEGRATION_HIKVISION,
        'provider' => AccessControlDevice::PROVIDER_HIKVISION_AGENT,
        'name' => 'HIK Entry Filter',
        'device_model' => AccessControlDevice::MODEL_DS_K1T808MFWX,
        'device_type' => AccessControlDevice::TYPE_ENTRY,
        'serial_number' => 'HIK-FILTER-01',
        'status' => AccessControlDevice::STATUS_ACTIVE,
    ]);

    $identity = AccessIdentity::query()->create([
        'branch_id' => $this->branch->id,
        'integration_type' => AccessControlDevice::INTEGRATION_HIKVISION,
        'provider' => AccessControlDevice::PROVIDER_HIKVISION_AGENT,
        'subject_type' => AccessIdentity::SUBJECT_MEMBER,
        'subject_id' => $member->id,
        'device_user_id' => 'MBR-HIK-FILTER',
        'is_active' => true,
    ]);

    AccessLog::query()->create([
        'branch_id' => $this->branch->id,
        'integration_type' => AccessControlDevice::INTEGRATION_HIKVISION,
        'provider' => AccessControlDevice::PROVIDER_HIKVISION_AGENT,
        'access_control_device_id' => $hikvision_device->id,
        'access_identity_id' => $identity->id,
        'device_event_uid' => 'hik-filter-uid',
        'subject_type' => AccessIdentity::SUBJECT_MEMBER,
        'subject_id' => $member->id,
        'direction' => AccessLog::DIRECTION_IN,
        'event_timestamp' => now()->subMinutes(30),
        'raw_payload' => ['source' => 'hikvision'],
    ]);

    $connection = ZktecoConnection::query()->withoutBranchScope()->create([
        'branch_id' => $this->branch->id,
        'provider' => ZktecoConnection::PROVIDER_ZKBIO_API,
        'status' => ZktecoConnection::STATUS_CONNECTED,
        'base_url' => 'http://127.0.0.1:4370',
    ]);

    $zkteco_device = ZktecoDevice::query()->withoutBranchScope()->create([
        'zkteco_connection_id' => $connection->id,
        'branch_id' => $this->branch->id,
        'remote_device_id' => 'ZK-FILTER-1',
        'remote_name' => 'ZK Filter Gate',
    ]);

    ZktecoAccessEvent::query()->withoutBranchScope()->create([
        'branch_id' => $this->branch->id,
        'zkteco_connection_id' => $connection->id,
        'zkteco_device_id' => $zkteco_device->id,
        'member_id' => $member->id,
        'remote_event_id' => 'zk-filter-uid',
        'event_fingerprint' => sha1('zk-filter-uid'),
        'remote_personnel_id' => 'MBR-ZK-FILTER',
        'direction' => ZktecoAccessEvent::DIRECTION_IN,
        'occurred_at' => now()->subMinutes(20),
        'matched_member' => true,
        'raw_payload' => ['source' => 'zkteco'],
    ]);

    Livewire::test(AttendanceReport::class)
        ->set('date_from', now()->subDay()->format('Y-m-d'))
        ->set('date_to', now()->format('Y-m-d'))
        ->set('integration_type', AccessControlDevice::INTEGRATION_ZKTECO)
        ->assertDontSee('HIK Entry Filter')
        ->assertSee('ZK Filter Gate')
        ->assertDontSee('hik-filter-uid')
        ->assertSee('zk-filter-uid');
});
