<?php

use App\Livewire\CvSecurity\Connections\Form as CvSecurityConnectionForm;
use App\Models\Branch;
use App\Models\CvSecurityAgent;
use App\Models\CvSecurityConnection;
use App\Models\CvSecurityMemberSyncItem;
use App\Models\Member;
use App\Models\User;
use App\Services\CvSecurity\PairingService;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

function cvSign(string $token, string $method, string $path, array $payload, int $timestamp): string
{
    $content = $payload === [] ? '' : json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return hash_hmac('sha256', $timestamp . "\n" . strtoupper($method) . "\n" . $path . "\n" . $content, $token);
}

function cvAgentHeaders(string $uuid, string $token, string $method, string $path, array $payload = []): array
{
    $timestamp = time();
    return [
        'X-CV-Agent-UUID' => $uuid,
        'X-CV-Agent-Token' => $token,
        'X-CV-Timestamp' => (string) $timestamp,
        'X-CV-Signature' => cvSign($token, $method, $path, $payload, $timestamp),
    ];
}

beforeEach(function () {
    foreach (['view zkteco', 'manage zkteco', 'manage zkteco settings', 'switch branches'] as $permission) {
        Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
    }
});

it('creates cvsecurity connection settings via livewire form with encrypted secrets', function () {
    $branch = Branch::factory()->create();
    $user = User::factory()->create(['branch_id' => $branch->id]);
    $user->givePermissionTo('manage zkteco settings');

    $this->actingAs($user);

    Livewire::test(CvSecurityConnectionForm::class)
        ->set('branch_id', $branch->id)
        ->set('name', 'Main CVSecurity')
        ->set('agent_label', 'Front Desk PC')
        ->set('cv_base_url', 'http://192.168.1.10')
        ->set('cv_port', 4370)
        ->set('cv_username', 'admin')
        ->set('cv_password', 'secret-password')
        ->set('cv_api_token', 'api-secret-token')
        ->set('poll_interval_seconds', 30)
        ->set('timezone', 'Africa/Nairobi')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('cvsecurity_connections', [
        'branch_id' => $branch->id,
        'name' => 'Main CVSecurity',
        'cv_base_url' => 'http://192.168.1.10',
        'cv_port' => 4370,
    ]);

    $row = DB::table('cvsecurity_connections')->where('name', 'Main CVSecurity')->first();
    expect($row)->not()->toBeNull();
    expect($row->cv_password_encrypted)->not()->toBe('secret-password');
    expect($row->cv_api_token_encrypted)->not()->toBe('api-secret-token');
});

it('pairs an agent with a one-time pairing token', function () {
    $branch = Branch::factory()->create();
    $admin = User::factory()->create(['branch_id' => $branch->id]);
    $admin->givePermissionTo('manage zkteco settings');

    $connection = CvSecurityConnection::query()->withoutBranchScope()->create([
        'branch_id' => $branch->id,
        'name' => 'Branch A',
        'cv_base_url' => 'http://192.168.1.25',
        'status' => CvSecurityConnection::STATUS_PENDING,
        'pairing_status' => CvSecurityConnection::PAIRING_UNPAIRED,
        'created_by' => $admin->id,
    ]);

    $token = app(PairingService::class)->generateToken($connection, $admin, 30);

    $response = $this->postJson('/api/cvsecurity/agent/pair', [
        'pairing_token' => $token['plaintext_token'],
        'agent_name' => 'Reception Agent',
        'os' => 'windows',
        'app_version' => '1.0.0',
    ]);

    $response->assertOk()
        ->assertJsonStructure([
            'connection_id',
            'branch_id',
            'agent_uuid',
            'agent_token',
            'poll_interval_seconds',
            'server_time',
        ]);

    $agent = CvSecurityAgent::query()->where('uuid', $response->json('agent_uuid'))->first();
    expect($agent)->not()->toBeNull();
    expect($agent->auth_token_hash)->toBe(hash('sha256', (string) $response->json('agent_token')));
});

it('accepts signed heartbeat and updates connection health', function () {
    $branch = Branch::factory()->create();
    $admin = User::factory()->create(['branch_id' => $branch->id]);
    $admin->givePermissionTo('manage zkteco settings');

    $connection = CvSecurityConnection::query()->withoutBranchScope()->create([
        'branch_id' => $branch->id,
        'name' => 'Branch B',
        'cv_base_url' => 'http://192.168.1.30',
        'status' => CvSecurityConnection::STATUS_PENDING,
        'pairing_status' => CvSecurityConnection::PAIRING_UNPAIRED,
        'created_by' => $admin->id,
    ]);

    $token = app(PairingService::class)->generateToken($connection, $admin, 30);
    $pair = $this->postJson('/api/cvsecurity/agent/pair', [
        'pairing_token' => $token['plaintext_token'],
        'agent_name' => 'Heartbeat Agent',
    ])->assertOk();

    $uuid = (string) $pair->json('agent_uuid');
    $agent_token = (string) $pair->json('agent_token');
    $payload = ['os' => 'windows', 'app_version' => '1.2.3'];

    $headers = cvAgentHeaders($uuid, $agent_token, 'POST', '/api/cvsecurity/agent/heartbeat', $payload);
    $heartbeat = $this->withHeaders($headers)->postJson('/api/cvsecurity/agent/heartbeat', $payload);
    $heartbeat->assertOk()->assertJsonPath('ok', true);

    $this->assertDatabaseHas('cvsecurity_connections', [
        'id' => $connection->id,
        'status' => CvSecurityConnection::STATUS_CONNECTED,
        'agent_status' => 'online',
    ]);
});

it('ingests events with deduplication through signed endpoint', function () {
    $branch = Branch::factory()->create();
    $admin = User::factory()->create(['branch_id' => $branch->id]);
    $admin->givePermissionTo('manage zkteco settings');

    $member = Member::factory()->create([
        'branch_id' => $branch->id,
        'member_no' => 'MBR-CV-001',
    ]);

    $connection = CvSecurityConnection::query()->withoutBranchScope()->create([
        'branch_id' => $branch->id,
        'name' => 'Branch C',
        'cv_base_url' => 'http://192.168.1.31',
        'status' => CvSecurityConnection::STATUS_PENDING,
        'pairing_status' => CvSecurityConnection::PAIRING_UNPAIRED,
        'created_by' => $admin->id,
    ]);

    $token = app(PairingService::class)->generateToken($connection, $admin, 30);
    $pair = $this->postJson('/api/cvsecurity/agent/pair', [
        'pairing_token' => $token['plaintext_token'],
        'agent_name' => 'Events Agent',
    ])->assertOk();

    $uuid = (string) $pair->json('agent_uuid');
    $agent_token = (string) $pair->json('agent_token');

    $payload = [
        'events' => [
            [
                'external_event_id' => 'EVT-1001',
                'external_person_id' => $member->member_no,
                'event_type' => 'entry_granted',
                'direction' => 'in',
                'occurred_at' => now()->toIso8601String(),
                'device_id' => 'door-1',
                'raw_payload' => ['source' => 'cvsecurity'],
            ],
        ],
    ];

    $headers = cvAgentHeaders($uuid, $agent_token, 'POST', '/api/cvsecurity/agent/events/push', $payload);
    $first = $this->withHeaders($headers)->postJson('/api/cvsecurity/agent/events/push', $payload);
    $first->assertOk()->assertJsonPath('stored', 1);

    $headers2 = cvAgentHeaders($uuid, $agent_token, 'POST', '/api/cvsecurity/agent/events/push', $payload);
    $second = $this->withHeaders($headers2)->postJson('/api/cvsecurity/agent/events/push', $payload);
    $second->assertOk()->assertJsonPath('duplicates', 1);

    $this->assertDatabaseCount('cvsecurity_events', 1);
});

it('denies zkteco integration page for user without zkteco permissions', function () {
    $branch = Branch::factory()->create();
    $user = User::factory()->create(['branch_id' => $branch->id]);

    $this->actingAs($user);
    $this->get(route('zkteco.connections.index'))->assertForbidden();
});

it('transitions sync item states from pending to processing to done', function () {
    $branch = Branch::factory()->create();
    $admin = User::factory()->create(['branch_id' => $branch->id]);
    $admin->givePermissionTo('manage zkteco settings');

    $member = Member::factory()->create([
        'branch_id' => $branch->id,
        'member_no' => 'MBR-CV-100',
    ]);

    $connection = CvSecurityConnection::query()->withoutBranchScope()->create([
        'branch_id' => $branch->id,
        'name' => 'Branch D',
        'cv_base_url' => 'http://192.168.1.44',
        'status' => CvSecurityConnection::STATUS_PENDING,
        'pairing_status' => CvSecurityConnection::PAIRING_UNPAIRED,
        'created_by' => $admin->id,
    ]);

    $sync_item = CvSecurityMemberSyncItem::query()->withoutBranchScope()->create([
        'cvsecurity_connection_id' => $connection->id,
        'branch_id' => $branch->id,
        'member_id' => $member->id,
        'sync_action' => 'upsert',
        'desired_state' => 'active',
        'external_person_id' => $member->member_no,
        'status' => 'pending',
        'payload' => ['member_id' => $member->id, 'external_person_id' => $member->member_no, 'active' => true],
    ]);

    $token = app(PairingService::class)->generateToken($connection, $admin, 30);
    $pair = $this->postJson('/api/cvsecurity/agent/pair', [
        'pairing_token' => $token['plaintext_token'],
        'agent_name' => 'Sync Agent',
    ])->assertOk();

    $uuid = (string) $pair->json('agent_uuid');
    $agent_token = (string) $pair->json('agent_token');

    $pull_headers = cvAgentHeaders($uuid, $agent_token, 'GET', '/api/cvsecurity/agent/member-sync/pull', []);
    $pull = $this->withHeaders($pull_headers)->getJson('/api/cvsecurity/agent/member-sync/pull?limit=10');
    $pull->assertOk()->assertJsonPath('count', 1);

    $sync_item->refresh();
    expect($sync_item->status)->toBe('processing');

    $result_payload = [
        'results' => [
            [
                'sync_item_id' => $sync_item->id,
                'status' => 'done',
                'result' => ['remote_id' => 'P-1'],
            ],
        ],
    ];

    $result_headers = cvAgentHeaders($uuid, $agent_token, 'POST', '/api/cvsecurity/agent/member-sync/results', $result_payload);
    $done = $this->withHeaders($result_headers)->postJson('/api/cvsecurity/agent/member-sync/results', $result_payload);
    $done->assertOk()->assertJsonPath('succeeded', 1);

    $sync_item->refresh();
    expect($sync_item->status)->toBe('done');
    expect($sync_item->processed_at)->not()->toBeNull();
});
