<?php

namespace App\Integrations\Zkteco\Contracts;

use App\Integrations\Zkteco\DTO\ConnectionTestResult;
use App\Models\ZktecoConnection;
use Illuminate\Support\Carbon;

interface ZktecoProvider
{
    public function key(): string;

    public function testConnection(ZktecoConnection $connection): ConnectionTestResult;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetchDevices(ZktecoConnection $connection): array;

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function upsertPersonnel(ZktecoConnection $connection, array $payload): array;

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function syncAccess(ZktecoConnection $connection, array $payload): array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function pullEvents(ZktecoConnection $connection, ?Carbon $since = null, ?Carbon $until = null): array;
}

