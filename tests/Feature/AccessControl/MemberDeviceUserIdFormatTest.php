<?php

use App\Models\AccessControlDevice;
use App\Models\AccessControlDeviceCommand;
use App\Models\Member;
use App\Services\AccessControl\AccessControlCommandService;
use Illuminate\Support\Str;

it('uses random unique 6-digit numeric value as hikvision member device_user_id', function () {
    $branch = \App\Models\Branch::factory()->create();

    $member_a = Member::factory()->create([
        'branch_id' => $branch->id,
        'member_no' => '20002',
    ]);
    $member_b = Member::factory()->create([
        'branch_id' => $branch->id,
        'member_no' => '20003',
    ]);

    AccessControlDevice::query()->create([
        'branch_id' => $branch->id,
        'integration_type' => AccessControlDevice::INTEGRATION_HIKVISION,
        'provider' => AccessControlDevice::PROVIDER_HIKVISION_AGENT,
        'name' => 'Hikvision Gate',
        'device_model' => AccessControlDevice::MODEL_DS_K1T808MFWX,
        'device_type' => AccessControlDevice::TYPE_ENTRY,
        'serial_number' => 'HK-' . Str::random(8),
        'status' => AccessControlDevice::STATUS_ACTIVE,
        'connection_status' => AccessControlDevice::CONNECTION_UNKNOWN,
        'auto_sync_enabled' => false,
        'sync_interval_minutes' => 5,
        'supports_face_recognition' => true,
        'supports_fingerprint' => true,
        'supports_card' => true,
    ]);

    $service = app(AccessControlCommandService::class);
    $identity_a = $service->ensure_access_identity_for_member($member_a);
    $identity_b = $service->ensure_access_identity_for_member($member_b);
    $identity_a_again = $service->ensure_access_identity_for_member($member_a);

    expect($identity_a->integration_type)->toBe(AccessControlDevice::INTEGRATION_HIKVISION);
    expect($identity_a->device_user_id)->toMatch('/^\d{6}$/');
    expect($identity_b->device_user_id)->toMatch('/^\d{6}$/');
    expect($identity_a->device_user_id)->not->toBe($identity_b->device_user_id);
    expect($identity_a_again->device_user_id)->toBe($identity_a->device_user_id);
});

it('keeps member_no as device_user_id for zkteco commands', function () {
    $branch = \App\Models\Branch::factory()->create();

    $member = Member::factory()->create([
        'branch_id' => $branch->id,
        'member_no' => '020001',
    ]);

    AccessControlDevice::query()->create([
        'branch_id' => $branch->id,
        'integration_type' => AccessControlDevice::INTEGRATION_ZKTECO,
        'provider' => AccessControlDevice::PROVIDER_ZKTECO_ZKBIO,
        'name' => 'ZKTeco Door',
        'device_model' => 'ZKTeco Terminal',
        'device_type' => AccessControlDevice::TYPE_ENTRY,
        'serial_number' => 'ZK-' . Str::random(8),
        'status' => AccessControlDevice::STATUS_ACTIVE,
        'connection_status' => AccessControlDevice::CONNECTION_UNKNOWN,
        'auto_sync_enabled' => false,
        'sync_interval_minutes' => 5,
        'supports_face_recognition' => true,
        'supports_fingerprint' => true,
        'supports_card' => true,
    ]);

    $service = app(AccessControlCommandService::class);
    $service->enqueueAccessSetValidityForMember(
        member: $member,
        valid_from: now()->subMinute()->format('Y-m-d H:i:s'),
        valid_to: now()->addDay()->endOfDay()->format('Y-m-d H:i:s'),
        integration_type: AccessControlDevice::INTEGRATION_ZKTECO,
        provider: AccessControlDevice::PROVIDER_ZKTECO_ZKBIO,
    );

    $command = AccessControlDeviceCommand::query()
        ->where('branch_id', $branch->id)
        ->where('integration_type', AccessControlDevice::INTEGRATION_ZKTECO)
        ->latest('id')
        ->firstOrFail();

    expect($command->payload['device_user_id'] ?? null)->toBe($member->member_no);
});

it('includes member gender in hikvision person_upsert payload', function () {
    $branch = \App\Models\Branch::factory()->create();

    $member = Member::factory()->create([
        'branch_id' => $branch->id,
        'member_no' => '31003',
        'gender' => 'female',
    ]);

    AccessControlDevice::query()->create([
        'branch_id' => $branch->id,
        'integration_type' => AccessControlDevice::INTEGRATION_HIKVISION,
        'provider' => AccessControlDevice::PROVIDER_HIKVISION_AGENT,
        'name' => 'Hikvision Gate 2',
        'device_model' => AccessControlDevice::MODEL_DS_K1T808MFWX,
        'device_type' => AccessControlDevice::TYPE_ENTRY,
        'serial_number' => 'HK-' . Str::random(8),
        'status' => AccessControlDevice::STATUS_ACTIVE,
        'connection_status' => AccessControlDevice::CONNECTION_UNKNOWN,
        'auto_sync_enabled' => false,
        'sync_interval_minutes' => 5,
        'supports_face_recognition' => true,
        'supports_fingerprint' => true,
        'supports_card' => true,
    ]);

    $service = app(AccessControlCommandService::class);
    $identity = $service->ensure_access_identity_for_member($member);
    $service->enqueueUserSync($member, $identity, now()->subMinute(), now()->addDay());

    $command = AccessControlDeviceCommand::query()
        ->where('branch_id', $branch->id)
        ->where('integration_type', AccessControlDevice::INTEGRATION_HIKVISION)
        ->where('type', AccessControlDeviceCommand::TYPE_PERSON_UPSERT)
        ->latest('id')
        ->firstOrFail();

    expect($command->payload['gender'] ?? null)->toBe('female');
});

it('replaces legacy hikvision member id pattern with random unique 6-digit value', function () {
    $branch = \App\Models\Branch::factory()->create();

    $member = Member::factory()->create([
        'branch_id' => $branch->id,
        'member_no' => '41003',
        'gender' => 'male',
    ]);

    AccessControlDevice::query()->create([
        'branch_id' => $branch->id,
        'integration_type' => AccessControlDevice::INTEGRATION_HIKVISION,
        'provider' => AccessControlDevice::PROVIDER_HIKVISION_AGENT,
        'name' => 'Hikvision Gate 3',
        'device_model' => AccessControlDevice::MODEL_DS_K1T808MFWX,
        'device_type' => AccessControlDevice::TYPE_ENTRY,
        'serial_number' => 'HK-' . Str::random(8),
        'status' => AccessControlDevice::STATUS_ACTIVE,
        'connection_status' => AccessControlDevice::CONNECTION_UNKNOWN,
        'auto_sync_enabled' => false,
        'sync_interval_minutes' => 5,
        'supports_face_recognition' => true,
        'supports_fingerprint' => true,
        'supports_card' => true,
    ]);

    $legacy = str_pad((string) $member->id, 6, '0', STR_PAD_LEFT);

    \App\Models\AccessIdentity::query()->create([
        'branch_id' => $branch->id,
        'integration_type' => AccessControlDevice::INTEGRATION_HIKVISION,
        'provider' => AccessControlDevice::PROVIDER_HIKVISION_AGENT,
        'subject_type' => \App\Models\AccessIdentity::SUBJECT_MEMBER,
        'subject_id' => $member->id,
        'device_user_id' => $legacy,
        'is_active' => true,
    ]);

    $service = app(AccessControlCommandService::class);
    $identity = $service->ensure_access_identity_for_member($member);

    expect($identity->device_user_id)->toMatch('/^\d{6}$/');
    expect($identity->device_user_id)->not->toBe($legacy);
});
