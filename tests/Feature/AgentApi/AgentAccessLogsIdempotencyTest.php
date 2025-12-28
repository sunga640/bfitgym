<?php

use App\Models\AccessControlAgent;
use App\Models\AccessControlDevice;
use App\Models\AccessIdentity;
use App\Models\Branch;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

test('log ingestion is idempotent by device_event_uid', function () {
    $branch = Branch::factory()->create();

    $token = Str::random(32);
    $agent = AccessControlAgent::create([
        'branch_id' => $branch->id,
        'uuid' => (string) Str::uuid(),
        'name' => 'Agent',
        'os' => 'windows',
        'app_version' => '1.0.0',
        'status' => AccessControlAgent::STATUS_ACTIVE,
        'secret_hash' => hash('sha256', $token),
    ]);

    $device = AccessControlDevice::create([
        'branch_id' => $branch->id,
        'access_control_agent_id' => $agent->id,
        'name' => 'Device A',
        'device_model' => AccessControlDevice::MODEL_DS_K1T808MFWX,
        'device_type' => AccessControlDevice::TYPE_ENTRY,
        'serial_number' => 'LOG-SN-' . Str::random(8),
        'ip_address' => null,
        'status' => AccessControlDevice::STATUS_ACTIVE,
        'connection_status' => AccessControlDevice::CONNECTION_UNKNOWN,
        'auto_sync_enabled' => false,
        'sync_interval_minutes' => 5,
        'supports_face_recognition' => true,
        'supports_fingerprint' => true,
        'supports_card' => true,
    ]);

    $member = Member::factory()->create(['branch_id' => $branch->id, 'status' => 'active']);

    AccessIdentity::create([
        'branch_id' => $branch->id,
        'subject_type' => 'member',
        'subject_id' => $member->id,
        'device_user_id' => $member->member_no,
        'card_number' => null,
        'is_active' => true,
    ]);

    $event_uid = 'EVT-' . Str::random(12);
    $payload = [
        'device_id' => $device->id,
        'events' => [
            [
                'device_event_uid' => $event_uid,
                'device_user_id' => $member->member_no,
                'event_timestamp' => Carbon::now()->toIso8601String(),
                'direction' => 'in',
                'raw_payload' => ['k' => 'v'],
            ],
            [
                'device_event_uid' => $event_uid,
                'device_user_id' => $member->member_no,
                'event_timestamp' => Carbon::now()->toIso8601String(),
                'direction' => 'in',
                'raw_payload' => ['k' => 'v'],
            ],
        ],
    ];

    $response = $this->withHeaders([
        'X-Agent-UUID' => $agent->uuid,
        'X-Agent-Token' => $token,
    ])->postJson('/api/agent/access-logs/batch', $payload);

    $response->assertOk();
    expect($response->json('inserted'))->toBe(1);
    expect($response->json('ignored'))->toBe(1);

    $this->assertDatabaseCount('access_logs', 1);

    // Retry same batch -> should insert 0, ignore 2
    $response2 = $this->withHeaders([
        'X-Agent-UUID' => $agent->uuid,
        'X-Agent-Token' => $token,
    ])->postJson('/api/agent/access-logs/batch', $payload);

    $response2->assertOk();
    expect($response2->json('inserted'))->toBe(0);
    expect($response2->json('ignored'))->toBe(2);
    $this->assertDatabaseCount('access_logs', 1);
});
