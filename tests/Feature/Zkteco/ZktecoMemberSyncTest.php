<?php

use App\Integrations\Zkteco\Services\ZktecoMemberSyncService;
use App\Models\Branch;
use App\Models\Member;
use App\Models\MemberSubscription;
use App\Models\MembershipPackage;
use App\Models\ZktecoBranchMapping;
use App\Models\ZktecoConnection;
use App\Models\ZktecoDevice;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

it('syncs eligible members and grants then revokes zkteco access based on membership state', function () {
    Carbon::setTestNow(Carbon::parse('2026-03-16 09:00:00'));

    $branch = Branch::factory()->create();
    $member = Member::factory()->create([
        'branch_id' => $branch->id,
        'status' => 'active',
    ]);

    $package = MembershipPackage::factory()->active()->create([
        'branch_id' => $branch->id,
    ]);

    MemberSubscription::query()->create([
        'branch_id' => $branch->id,
        'member_id' => $member->id,
        'membership_package_id' => $package->id,
        'renewed_from_id' => null,
        'start_date' => now()->subDay()->toDateString(),
        'end_date' => now()->addDays(30)->toDateString(),
        'status' => 'active',
        'auto_renew' => false,
        'notes' => null,
    ]);

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

    $device = ZktecoDevice::query()->withoutBranchScope()->create([
        'zkteco_connection_id' => $connection->id,
        'branch_id' => $branch->id,
        'remote_device_id' => 'DEV-001',
        'remote_name' => 'Front Turnstile',
        'remote_type' => 'turnstile',
        'is_online' => true,
        'is_assignable' => true,
    ]);

    ZktecoBranchMapping::query()->withoutBranchScope()->create([
        'branch_id' => $branch->id,
        'zkteco_connection_id' => $connection->id,
        'zkteco_device_id' => $device->id,
        'is_active' => true,
    ]);

    Http::fake([
        '*api/v1/access/personnel/upsert' => Http::response([
            'data' => [
                'personnel_id' => 'P-' . $member->id,
                'personnel_code' => $member->member_no,
                'enrollment_required' => true,
            ],
        ], 200),
        '*api/v1/access/rights/sync' => Http::response(['ok' => true], 200),
    ]);

    $first = app(ZktecoMemberSyncService::class)->syncBranch($connection);

    expect($first['failed'])->toBe(0);

    $this->assertDatabaseHas('zkteco_member_maps', [
        'branch_id' => $branch->id,
        'member_id' => $member->id,
        'remote_personnel_id' => 'P-' . $member->id,
        'access_active' => true,
        'biometric_status' => 'pending',
    ]);

    $this->assertDatabaseHas('zkteco_member_device_access', [
        'zkteco_device_id' => $device->id,
        'access_granted' => true,
    ]);

    $member->update(['status' => 'inactive']);

    Http::fake([
        '*api/v1/access/rights/sync' => Http::response(['ok' => true], 200),
    ]);

    $second = app(ZktecoMemberSyncService::class)->syncBranch($connection->fresh());

    expect($second['failed'])->toBe(0);

    $this->assertDatabaseHas('zkteco_member_maps', [
        'branch_id' => $branch->id,
        'member_id' => $member->id,
        'access_active' => false,
    ]);

    $this->assertDatabaseHas('zkteco_member_device_access', [
        'zkteco_device_id' => $device->id,
        'access_granted' => false,
    ]);

    Carbon::setTestNow();
});

