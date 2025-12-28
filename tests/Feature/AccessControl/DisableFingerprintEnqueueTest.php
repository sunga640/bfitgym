<?php

use App\Livewire\Members\Show as MemberShow;
use App\Models\AccessControlDevice;
use App\Models\AccessControlDeviceCommand;
use App\Models\AccessIdentity;
use App\Models\Branch;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

test('disable fingerprint enqueues access_set_validity command and writes access log markers', function () {
    $branch = Branch::factory()->create();

    Permission::firstOrCreate(['name' => 'view members', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'edit members', 'guard_name' => 'web']);
    // Some shared layout UI checks this permission and Spatie throws if the permission row doesn't exist.
    Permission::firstOrCreate(['name' => 'switch branches', 'guard_name' => 'web']);

    $user = User::factory()->create([
        'branch_id' => $branch->id,
    ]);
    $user->givePermissionTo(['view members', 'edit members']);

    $member = Member::factory()->create([
        'branch_id' => $branch->id,
    ]);

    AccessIdentity::create([
        'branch_id' => $branch->id,
        'subject_type' => AccessIdentity::SUBJECT_MEMBER,
        'subject_id' => $member->id,
        'device_user_id' => $member->member_no,
        'is_active' => true,
    ]);

    $device = AccessControlDevice::create([
        'branch_id' => $branch->id,
        'name' => 'Device A',
        'device_model' => AccessControlDevice::MODEL_DS_K1T808MFWX,
        'device_type' => AccessControlDevice::TYPE_ENTRY,
        'serial_number' => 'SN-' . Str::random(10),
        'ip_address' => null,
        'status' => AccessControlDevice::STATUS_ACTIVE,
        'connection_status' => AccessControlDevice::CONNECTION_UNKNOWN,
        'auto_sync_enabled' => false,
        'sync_interval_minutes' => 5,
        'supports_face_recognition' => true,
        'supports_fingerprint' => true,
        'supports_card' => true,
    ]);

    $log_path = storage_path('logs/access-test.log');
    @unlink($log_path);
    config(['logging.channels.access.path' => $log_path]);

    $this->actingAs($user);

    $expected_valid_to = Carbon::yesterday()->endOfDay()->format('Y-m-d H:i:s');

    Livewire::test(MemberShow::class, ['member' => $member])
        ->call('disableFingerprint')
        ->assertHasNoErrors();

    /** @var AccessControlDeviceCommand $cmd */
    $cmd = AccessControlDeviceCommand::query()
        ->where('branch_id', $branch->id)
        ->where('access_control_device_id', $device->id)
        ->where('subject_type', 'member')
        ->where('subject_id', $member->id)
        ->where('type', AccessControlDeviceCommand::TYPE_ACCESS_SET_VALIDITY)
        ->firstOrFail();

    expect($cmd->status)->toBe(AccessControlDeviceCommand::STATUS_PENDING);
    expect($cmd->payload['device_user_id'] ?? null)->toBe($member->member_no);
    expect($cmd->payload['valid_to'] ?? null)->toBe($expected_valid_to);

    expect(is_file($log_path))->toBeTrue();
    $log_contents = (string) file_get_contents($log_path);
    expect($log_contents)->toContain('fingerprint_disable_requested');
    expect($log_contents)->toContain('command_enqueued');
});

