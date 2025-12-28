<?php

use App\Models\AccessControlAgent;
use App\Models\AccessControlCommandAudit;
use App\Models\AccessControlDevice;
use App\Models\AccessControlDeviceCommand;
use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['agent.stale_claim_minutes' => 2]);
});

function createAgentSetup(): array
{
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
        'serial_number' => 'CMD-SN-' . Str::random(8),
        'ip_address' => null,
        'status' => AccessControlDevice::STATUS_ACTIVE,
        'connection_status' => AccessControlDevice::CONNECTION_UNKNOWN,
        'auto_sync_enabled' => false,
        'sync_interval_minutes' => 5,
        'supports_face_recognition' => true,
        'supports_fingerprint' => true,
        'supports_card' => true,
    ]);

    return [
        'branch' => $branch,
        'agent' => $agent,
        'token' => $token,
        'device' => $device,
    ];
}

test('commands claiming changes status to claimed', function () {
    $setup = createAgentSetup();
    $agent = $setup['agent'];
    $token = $setup['token'];
    $device = $setup['device'];
    $branch = $setup['branch'];

    $command_id = (string) Str::uuid();
    AccessControlDeviceCommand::create([
        'id' => $command_id,
        'branch_id' => $branch->id,
        'access_control_device_id' => $device->id,
        'subject_type' => 'member',
        'subject_id' => 123,
        'type' => AccessControlDeviceCommand::TYPE_PERSON_DISABLE,
        'priority' => 20,
        'status' => AccessControlDeviceCommand::STATUS_PENDING,
        'attempts' => 0,
        'max_attempts' => 10,
        'available_at' => null,
        'payload' => ['device_user_id' => 'MBR-ABC123'],
    ]);

    $response = $this->withHeaders([
        'X-Agent-UUID' => $agent->uuid,
        'X-Agent-Token' => $token,
    ])->getJson('/api/agent/commands?limit=50');

    $response->assertOk();
    $response->assertJsonCount(1, 'commands');
    expect($response->json('commands.0.id'))->toBe($command_id);

    $this->assertDatabaseHas('access_control_device_commands', [
        'id' => $command_id,
        'status' => AccessControlDeviceCommand::STATUS_CLAIMED,
        'claimed_by_agent_id' => $agent->id,
    ]);

    $this->assertDatabaseHas('access_control_command_audits', [
        'command_id' => $command_id,
        'agent_id' => $agent->id,
        'status' => AccessControlCommandAudit::STATUS_RECEIVED,
    ]);
});

test('peek=1 does not claim commands', function () {
    $setup = createAgentSetup();
    $agent = $setup['agent'];
    $token = $setup['token'];
    $device = $setup['device'];
    $branch = $setup['branch'];

    $pending_id = (string) Str::uuid();
    AccessControlDeviceCommand::create([
        'id' => $pending_id,
        'branch_id' => $branch->id,
        'access_control_device_id' => $device->id,
        'subject_type' => 'member',
        'subject_id' => 123,
        'type' => AccessControlDeviceCommand::TYPE_PERSON_DISABLE,
        'priority' => 20,
        'status' => AccessControlDeviceCommand::STATUS_PENDING,
        'attempts' => 0,
        'max_attempts' => 10,
        'available_at' => null,
        'payload' => ['device_user_id' => 'MBR-ABC123'],
    ]);

    $claimed_id = (string) Str::uuid();
    AccessControlDeviceCommand::create([
        'id' => $claimed_id,
        'branch_id' => $branch->id,
        'access_control_device_id' => $device->id,
        'subject_type' => 'member',
        'subject_id' => 456,
        'type' => AccessControlDeviceCommand::TYPE_PERSON_UPSERT,
        'priority' => 10,
        'status' => AccessControlDeviceCommand::STATUS_CLAIMED,
        'claimed_by_agent_id' => $agent->id,
        'claimed_at' => now(),
        'attempts' => 0,
        'max_attempts' => 10,
        'available_at' => null,
        'payload' => ['device_user_id' => 'MBR-RESUME'],
    ]);

    // Peek request
    $response = $this->withHeaders([
        'X-Agent-UUID' => $agent->uuid,
        'X-Agent-Token' => $token,
    ])->getJson('/api/agent/commands?limit=50&peek=1');

    $response->assertOk();
    $response->assertJsonCount(2, 'commands');
    $ids = collect($response->json('commands'))->pluck('id')->toArray();
    expect($ids)->toContain($pending_id);
    expect($ids)->toContain($claimed_id);

    // Command should still be pending, not claimed
    $this->assertDatabaseHas('access_control_device_commands', [
        'id' => $pending_id,
        'status' => AccessControlDeviceCommand::STATUS_PENDING,
        'claimed_by_agent_id' => null,
    ]);

    // Claimed command should remain claimed (peek is read-only)
    $this->assertDatabaseHas('access_control_device_commands', [
        'id' => $claimed_id,
        'status' => AccessControlDeviceCommand::STATUS_CLAIMED,
        'claimed_by_agent_id' => $agent->id,
    ]);

    // No audit should be created
    $this->assertDatabaseMissing('access_control_command_audits', [
        'command_id' => $pending_id,
    ]);
});

test('subsequent pull returns already-claimed commands for same agent (resume)', function () {
    $setup = createAgentSetup();
    $agent = $setup['agent'];
    $token = $setup['token'];
    $device = $setup['device'];
    $branch = $setup['branch'];

    $command_id = (string) Str::uuid();
    AccessControlDeviceCommand::create([
        'id' => $command_id,
        'branch_id' => $branch->id,
        'access_control_device_id' => $device->id,
        'subject_type' => 'member',
        'subject_id' => 123,
        'type' => AccessControlDeviceCommand::TYPE_PERSON_DISABLE,
        'priority' => 20,
        'status' => AccessControlDeviceCommand::STATUS_CLAIMED,
        'claimed_by_agent_id' => $agent->id,
        'claimed_at' => now(),
        'attempts' => 0,
        'max_attempts' => 10,
        'available_at' => null,
        'payload' => ['device_user_id' => 'MBR-ABC123'],
    ]);

    // Pull should return the already-claimed command
    $response = $this->withHeaders([
        'X-Agent-UUID' => $agent->uuid,
        'X-Agent-Token' => $token,
    ])->getJson('/api/agent/commands?limit=50');

    $response->assertOk();
    $response->assertJsonCount(1, 'commands');
    expect($response->json('commands.0.id'))->toBe($command_id);

    // Command should still be claimed by same agent
    $this->assertDatabaseHas('access_control_device_commands', [
        'id' => $command_id,
        'status' => AccessControlDeviceCommand::STATUS_CLAIMED,
        'claimed_by_agent_id' => $agent->id,
    ]);
});

test('stale claims get released after TTL', function () {
    $setup = createAgentSetup();
    $agent = $setup['agent'];
    $token = $setup['token'];
    $device = $setup['device'];
    $branch = $setup['branch'];

    // Create a command that was claimed 5 minutes ago (stale)
    $stale_command_id = (string) Str::uuid();
    AccessControlDeviceCommand::create([
        'id' => $stale_command_id,
        'branch_id' => $branch->id,
        'access_control_device_id' => $device->id,
        'subject_type' => 'member',
        'subject_id' => 123,
        'type' => AccessControlDeviceCommand::TYPE_PERSON_DISABLE,
        'priority' => 20,
        'status' => AccessControlDeviceCommand::STATUS_CLAIMED,
        'claimed_by_agent_id' => $agent->id,
        'claimed_at' => now()->subMinutes(5), // 5 minutes ago, beyond 2-minute TTL
        'finished_at' => null,
        'attempts' => 0,
        'max_attempts' => 10,
        'available_at' => null,
        'payload' => ['device_user_id' => 'MBR-ABC123'],
    ]);

    // Pull request should release the stale claim and reclaim it
    $response = $this->withHeaders([
        'X-Agent-UUID' => $agent->uuid,
        'X-Agent-Token' => $token,
    ])->getJson('/api/agent/commands?limit=50');

    $response->assertOk();
    $response->assertJsonCount(1, 'commands');
    expect($response->json('commands.0.id'))->toBe($stale_command_id);

    // Command should be reclaimed (status='claimed') after release
    $this->assertDatabaseHas('access_control_device_commands', [
        'id' => $stale_command_id,
        'status' => AccessControlDeviceCommand::STATUS_CLAIMED,
        'claimed_by_agent_id' => $agent->id,
    ]);
});

test('when agent has resumed commands, it does not claim new pending commands in same poll', function () {
    $setup = createAgentSetup();
    $agent = $setup['agent'];
    $token = $setup['token'];
    $device = $setup['device'];
    $branch = $setup['branch'];

    // Create an already-claimed command (to be resumed)
    $resumed_id = (string) Str::uuid();
    AccessControlDeviceCommand::create([
        'id' => $resumed_id,
        'branch_id' => $branch->id,
        'access_control_device_id' => $device->id,
        'subject_type' => 'member',
        'subject_id' => 123,
        'type' => AccessControlDeviceCommand::TYPE_PERSON_DISABLE,
        'priority' => 10,
        'status' => AccessControlDeviceCommand::STATUS_CLAIMED,
        'claimed_by_agent_id' => $agent->id,
        'claimed_at' => now()->subSeconds(30),
        'attempts' => 0,
        'max_attempts' => 10,
        'payload' => ['device_user_id' => 'MBR-RESUME'],
    ]);

    // Create a pending command (to be newly claimed)
    $new_id = (string) Str::uuid();
    AccessControlDeviceCommand::create([
        'id' => $new_id,
        'branch_id' => $branch->id,
        'access_control_device_id' => $device->id,
        'subject_type' => 'member',
        'subject_id' => 456,
        'type' => AccessControlDeviceCommand::TYPE_PERSON_UPSERT,
        'priority' => 20,
        'status' => AccessControlDeviceCommand::STATUS_PENDING,
        'attempts' => 0,
        'max_attempts' => 10,
        'payload' => ['device_user_id' => 'MBR-NEW'],
    ]);

    $response = $this->withHeaders([
        'X-Agent-UUID' => $agent->uuid,
        'X-Agent-Token' => $token,
    ])->getJson('/api/agent/commands?limit=50');

    $response->assertOk();
    $response->assertJsonCount(1, 'commands');

    expect($response->json('commands.0.id'))->toBe($resumed_id);

    // Resumed should still be claimed now
    $this->assertDatabaseHas('access_control_device_commands', [
        'id' => $resumed_id,
        'status' => AccessControlDeviceCommand::STATUS_CLAIMED,
        'claimed_by_agent_id' => $agent->id,
    ]);

    // Pending should remain pending (not claimed yet)
    $this->assertDatabaseHas('access_control_device_commands', [
        'id' => $new_id,
        'status' => AccessControlDeviceCommand::STATUS_PENDING,
        'claimed_by_agent_id' => null,
    ]);
});

test('agent cannot claim commands from other agent', function () {
    $setup = createAgentSetup();
    $agent1 = $setup['agent'];
    $token1 = $setup['token'];
    $device = $setup['device'];
    $branch = $setup['branch'];

    // Create second agent
    $token2 = Str::random(32);
    $agent2 = AccessControlAgent::create([
        'branch_id' => $branch->id,
        'uuid' => (string) Str::uuid(),
        'name' => 'Agent 2',
        'os' => 'linux',
        'app_version' => '1.0.0',
        'status' => AccessControlAgent::STATUS_ACTIVE,
        'secret_hash' => hash('sha256', $token2),
    ]);

    // Create command claimed by agent 1
    $command_id = (string) Str::uuid();
    AccessControlDeviceCommand::create([
        'id' => $command_id,
        'branch_id' => $branch->id,
        'access_control_device_id' => $device->id,
        'subject_type' => 'member',
        'subject_id' => 123,
        'type' => AccessControlDeviceCommand::TYPE_PERSON_DISABLE,
        'priority' => 20,
        'status' => AccessControlDeviceCommand::STATUS_CLAIMED,
        'claimed_by_agent_id' => $agent1->id,
        'claimed_at' => now()->subSeconds(30),
        'attempts' => 0,
        'max_attempts' => 10,
        'payload' => ['device_user_id' => 'MBR-ABC123'],
    ]);

    // Agent 2 pulls - should not get agent 1's claimed command
    $response = $this->withHeaders([
        'X-Agent-UUID' => $agent2->uuid,
        'X-Agent-Token' => $token2,
    ])->getJson('/api/agent/commands?limit=50');

    $response->assertOk();
    $response->assertJsonCount(0, 'commands');

    // Command should still belong to agent 1
    $this->assertDatabaseHas('access_control_device_commands', [
        'id' => $command_id,
        'claimed_by_agent_id' => $agent1->id,
    ]);
});

test('processing status commands are also resumed', function () {
    $setup = createAgentSetup();
    $agent = $setup['agent'];
    $token = $setup['token'];
    $device = $setup['device'];
    $branch = $setup['branch'];

    $command_id = (string) Str::uuid();
    AccessControlDeviceCommand::create([
        'id' => $command_id,
        'branch_id' => $branch->id,
        'access_control_device_id' => $device->id,
        'subject_type' => 'member',
        'subject_id' => 123,
        'type' => AccessControlDeviceCommand::TYPE_PERSON_DISABLE,
        'priority' => 20,
        'status' => AccessControlDeviceCommand::STATUS_PROCESSING, // Processing status
        'claimed_by_agent_id' => $agent->id,
        'claimed_at' => now()->subSeconds(30),
        'processing_started_at' => now()->subSeconds(20),
        'attempts' => 1,
        'max_attempts' => 10,
        'payload' => ['device_user_id' => 'MBR-ABC123'],
    ]);

    $response = $this->withHeaders([
        'X-Agent-UUID' => $agent->uuid,
        'X-Agent-Token' => $token,
    ])->getJson('/api/agent/commands?limit=50');

    $response->assertOk();
    $response->assertJsonCount(1, 'commands');
    expect($response->json('commands.0.id'))->toBe($command_id);
});
