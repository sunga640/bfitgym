<?php

use App\Integrations\Zkteco\Services\ZktecoConnectionService;
use App\Models\Branch;
use App\Models\ZktecoConnection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

it('stores zkteco credentials encrypted at rest', function () {
    $branch = Branch::factory()->create();

    $connection = app(ZktecoConnectionService::class)->saveConnection($branch->id, [
        'base_url' => 'https://zkbio.example.com',
        'port' => 8443,
        'username' => 'admin',
        'password' => 'plain-password',
        'api_key' => 'plain-api-key',
        'ssl_enabled' => true,
        'allow_self_signed' => false,
        'timeout_seconds' => 10,
    ]);

    $row = DB::table('zkteco_connections')->where('id', $connection->id)->first();

    expect($row)->not()->toBeNull();
    expect($row->password)->not()->toBe('plain-password');
    expect($row->api_key)->not()->toBe('plain-api-key');

    $fresh = ZktecoConnection::query()->withoutBranchScope()->findOrFail($connection->id);

    expect($fresh->password)->toBe('plain-password');
    expect($fresh->api_key)->toBe('plain-api-key');
});

it('marks connection as connected when zkbio health check succeeds', function () {
    $branch = Branch::factory()->create();

    $connection = ZktecoConnection::query()->withoutBranchScope()->create([
        'branch_id' => $branch->id,
        'provider' => ZktecoConnection::PROVIDER_ZKBIO_API,
        'status' => ZktecoConnection::STATUS_DISCONNECTED,
        'base_url' => 'https://zkbio.example.com',
        'api_key' => 'any-secret',
        'ssl_enabled' => true,
        'allow_self_signed' => true,
        'timeout_seconds' => 10,
    ]);

    Http::fake([
        '*api/v1/system/health' => Http::response(['version' => '2.0.0'], 200),
    ]);

    $result = app(ZktecoConnectionService::class)->testConnection($connection);

    expect($result->ok)->toBeTrue();
    expect($result->status)->toBe(ZktecoConnection::STATUS_CONNECTED);

    $this->assertDatabaseHas('zkteco_connections', [
        'id' => $connection->id,
        'status' => ZktecoConnection::STATUS_CONNECTED,
    ]);
});

it('marks unsupported mode when zkbio api endpoint is unavailable', function () {
    $branch = Branch::factory()->create();

    $connection = ZktecoConnection::query()->withoutBranchScope()->create([
        'branch_id' => $branch->id,
        'provider' => ZktecoConnection::PROVIDER_ZKBIO_API,
        'status' => ZktecoConnection::STATUS_DISCONNECTED,
        'base_url' => 'https://zkbio.example.com',
        'api_key' => 'any-secret',
        'ssl_enabled' => true,
        'allow_self_signed' => false,
        'timeout_seconds' => 10,
    ]);

    Http::fake([
        '*api/v1/system/health' => Http::response([], 404),
    ]);

    $result = app(ZktecoConnectionService::class)->testConnection($connection);

    expect($result->ok)->toBeFalse();
    expect($result->status)->toBe(ZktecoConnection::STATUS_UNSUPPORTED);

    $this->assertDatabaseHas('zkteco_connections', [
        'id' => $connection->id,
        'status' => ZktecoConnection::STATUS_UNSUPPORTED,
    ]);
});

