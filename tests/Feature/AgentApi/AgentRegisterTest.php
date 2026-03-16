<?php

use App\Models\AccessControlAgent;
use App\Models\AccessControlAgentEnrollment;
use App\Models\AccessControlDevice;
use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

test('register success and token not stored plaintext', function () {
    $branch = Branch::factory()->create();

    $enrollment = AccessControlAgentEnrollment::create([
        'branch_id' => $branch->id,
        'code' => 'ENROLL-123',
        'expires_at' => now()->addMinutes(30),
        'created_by' => null,
        'used_at' => null,
        'used_by_agent_id' => null,
    ]);

    AccessControlDevice::create([
        'branch_id' => $branch->id,
        'name' => 'Device A',
        'device_model' => AccessControlDevice::MODEL_DS_K1T808MFWX,
        'device_type' => AccessControlDevice::TYPE_ENTRY,
        'serial_number' => 'REG-SN-' . Str::random(8),
        'ip_address' => '10.0.0.5',
        'port' => 80,
        'username' => 'admin',
        'password' => 'should-not-leak',
        'status' => AccessControlDevice::STATUS_ACTIVE,
        'connection_status' => AccessControlDevice::CONNECTION_UNKNOWN,
        'auto_sync_enabled' => false,
        'sync_interval_minutes' => 5,
        'supports_face_recognition' => true,
        'supports_fingerprint' => true,
        'supports_card' => true,
    ]);

    $response = $this->postJson('/api/agent/register', [
        'enrollment_code' => $enrollment->code,
        'name' => 'Front Desk PC',
        'os' => 'windows',
        'app_version' => '1.0.0',
    ]);

    $response->assertOk();
    $response->assertJsonStructure([
        'agent_uuid',
        'agent_token',
        'devices',
        'server_time',
    ]);

    $agent_uuid = $response->json('agent_uuid');
    $token = $response->json('agent_token');

    expect($agent_uuid)->not->toBeEmpty();
    expect($token)->not->toBeEmpty();

    $agent = AccessControlAgent::query()->where('uuid', $agent_uuid)->first();
    expect($agent)->not->toBeNull();

    expect($agent->secret_hash)->toBe(hash('sha256', $token));
    expect($agent->secret_hash)->not->toBe($token);

    $enrollment->refresh();
    expect($enrollment->used_at)->not->toBeNull();
    expect($enrollment->used_by_agent_id)->toBe($agent->id);

    $first_device = $response->json('devices.0');
    expect($first_device)->toBeArray();
    expect(array_key_exists('password_encrypted', $first_device))->toBeFalse();
});

test('register accepts legacy mac os alias', function () {
    $branch = Branch::factory()->create();

    $enrollment = AccessControlAgentEnrollment::create([
        'branch_id' => $branch->id,
        'code' => 'ENROLL-MAC-123',
        'expires_at' => now()->addMinutes(30),
        'created_by' => null,
        'used_at' => null,
        'used_by_agent_id' => null,
    ]);

    $response = $this->postJson('/api/agent/register', [
        'enrollment_code' => $enrollment->code,
        'name' => 'Mac Front Desk',
        'os' => 'mac',
        'app_version' => '1.0.0',
    ]);

    $response->assertOk();

    $agent = AccessControlAgent::query()->where('uuid', $response->json('agent_uuid'))->first();
    expect($agent)->not->toBeNull();
    expect($agent->os)->toBe('macos');
});

test('register returns specific error for already used enrollment code', function () {
    $branch = Branch::factory()->create();

    AccessControlAgentEnrollment::create([
        'branch_id' => $branch->id,
        'code' => 'ENROLL-USED-123',
        'status' => AccessControlAgentEnrollment::STATUS_USED,
        'expires_at' => now()->addMinutes(30),
        'created_by' => null,
        'used_at' => now()->subMinute(),
        'used_by_agent_id' => null,
    ]);

    $response = $this->postJson('/api/agent/register', [
        'enrollment_code' => 'ENROLL-USED-123',
        'name' => 'Front Desk PC',
        'os' => 'windows',
        'app_version' => '1.0.0',
    ]);

    $response->assertStatus(422);
    expect((string) $response->json('message'))->toContain('already used');
});
