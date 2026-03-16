<?php

namespace App\Integrations\Zkteco\Providers;

use App\Integrations\Zkteco\Clients\ZkbioClient;
use App\Integrations\Zkteco\Contracts\ZktecoProvider;
use App\Integrations\Zkteco\DTO\ConnectionTestResult;
use App\Integrations\Zkteco\Exceptions\ZktecoIntegrationException;
use App\Models\ZktecoConnection;
use App\Models\ZktecoMemberMap;
use Illuminate\Support\Carbon;

class ZkbioApiProvider implements ZktecoProvider
{
    public function __construct(
        private readonly ZkbioClient $client
    ) {
    }

    public function key(): string
    {
        return ZktecoConnection::PROVIDER_ZKBIO_API;
    }

    public function testConnection(ZktecoConnection $connection): ConnectionTestResult
    {
        try {
            $health = $this->client->health($connection);
        } catch (ZktecoIntegrationException $e) {
            return new ConnectionTestResult(
                ok: false,
                status: $this->statusFromReason($e->reason),
                message: $e->getMessage(),
                details: $e->context
            );
        }

        return new ConnectionTestResult(
            ok: true,
            status: ZktecoConnection::STATUS_CONNECTED,
            message: 'ZKBio API reachable.',
            details: [
                'remote_version' => data_get($health, 'version') ?? data_get($health, 'data.version'),
                'raw' => $health,
            ]
        );
    }

    public function fetchDevices(ZktecoConnection $connection): array
    {
        $devices = $this->client->devices($connection);

        $normalized = [];
        foreach ($devices as $device) {
            $remote_id = (string) (
                data_get($device, 'id')
                ?? data_get($device, 'deviceId')
                ?? data_get($device, 'device_id')
                ?? data_get($device, 'serialNo')
                ?? data_get($device, 'sn')
                ?? ''
            );

            if ($remote_id === '') {
                continue;
            }

            $status = (string) (data_get($device, 'status') ?? data_get($device, 'state') ?? '');
            $online_flag = data_get($device, 'isOnline');
            if (!is_bool($online_flag)) {
                $online_flag = data_get($device, 'online');
            }
            if (!is_bool($online_flag)) {
                $online_flag = in_array(strtolower($status), ['online', 'connected', 'active'], true);
            }

            $seen_at_raw = data_get($device, 'lastSeen')
                ?? data_get($device, 'last_seen')
                ?? data_get($device, 'heartbeatAt');

            $last_seen_at = null;
            if (!empty($seen_at_raw)) {
                try {
                    $last_seen_at = Carbon::parse((string) $seen_at_raw);
                } catch (\Throwable) {
                    $last_seen_at = null;
                }
            }

            $normalized[] = [
                'remote_device_id' => $remote_id,
                'remote_name' => (string) (
                    data_get($device, 'name')
                    ?? data_get($device, 'deviceName')
                    ?? data_get($device, 'alias')
                    ?? $remote_id
                ),
                'remote_type' => (string) (
                    data_get($device, 'type')
                    ?? data_get($device, 'deviceType')
                    ?? data_get($device, 'channelType')
                    ?? 'device'
                ),
                'remote_status' => $status !== '' ? $status : null,
                'is_online' => (bool) $online_flag,
                'is_assignable' => (bool) (data_get($device, 'isAssignable') ?? data_get($device, 'assignable') ?? true),
                'last_seen_at' => $last_seen_at,
                'remote_payload' => $device,
            ];
        }

        return $normalized;
    }

    public function upsertPersonnel(ZktecoConnection $connection, array $payload): array
    {
        $response = $this->client->upsertPersonnel($connection, $payload);

        $remote_personnel_id = (string) (
            data_get($response, 'data.personnel_id')
            ?? data_get($response, 'data.id')
            ?? data_get($response, 'personnel_id')
            ?? data_get($response, 'id')
            ?? ''
        );

        if ($remote_personnel_id === '') {
            throw ZktecoIntegrationException::unsupportedApi('Personnel sync succeeded but no remote personnel ID was returned.');
        }

        $remote_personnel_code = (string) (
            data_get($response, 'data.personnel_code')
            ?? data_get($response, 'data.code')
            ?? data_get($response, 'personnel_code')
            ?? data_get($payload, 'personnel_code')
            ?? ''
        );

        $biometric_enrolled = data_get($response, 'data.biometric_enrolled');
        $enrollment_required = data_get($response, 'data.enrollment_required');

        $biometric_status = ZktecoMemberMap::BIOMETRIC_UNKNOWN;
        if ($biometric_enrolled === true) {
            $biometric_status = ZktecoMemberMap::BIOMETRIC_ENROLLED;
        } elseif ($enrollment_required === true || $biometric_enrolled === false) {
            $biometric_status = ZktecoMemberMap::BIOMETRIC_PENDING;
        }

        return [
            'remote_personnel_id' => $remote_personnel_id,
            'remote_personnel_code' => $remote_personnel_code !== '' ? $remote_personnel_code : null,
            'biometric_status' => $biometric_status,
            'raw' => $response,
        ];
    }

    public function syncAccess(ZktecoConnection $connection, array $payload): array
    {
        return $this->client->syncAccess($connection, $payload);
    }

    public function pullEvents(ZktecoConnection $connection, ?Carbon $since = null, ?Carbon $until = null): array
    {
        $events = $this->client->events($connection, $since, $until);
        $normalized = [];

        foreach ($events as $event) {
            $occurred_at_raw = data_get($event, 'occurred_at')
                ?? data_get($event, 'occurredAt')
                ?? data_get($event, 'timestamp')
                ?? data_get($event, 'time')
                ?? null;

            if (!$occurred_at_raw) {
                continue;
            }

            try {
                $occurred_at = Carbon::parse((string) $occurred_at_raw);
            } catch (\Throwable) {
                continue;
            }

            $direction_value = strtolower((string) (
                data_get($event, 'direction')
                ?? data_get($event, 'io')
                ?? data_get($event, 'event_type')
                ?? ''
            ));

            $direction = match ($direction_value) {
                'in', 'entry', 'enter', 'check_in' => 'in',
                'out', 'exit', 'leave', 'check_out' => 'out',
                default => 'unknown',
            };

            $normalized[] = [
                'remote_event_id' => (string) (
                    data_get($event, 'id')
                    ?? data_get($event, 'eventId')
                    ?? data_get($event, 'event_id')
                    ?? data_get($event, 'logId')
                    ?? ''
                ) ?: null,
                'remote_device_id' => (string) (
                    data_get($event, 'deviceId')
                    ?? data_get($event, 'device_id')
                    ?? data_get($event, 'terminalId')
                    ?? data_get($event, 'doorId')
                    ?? ''
                ) ?: null,
                'remote_personnel_id' => (string) (
                    data_get($event, 'personnelId')
                    ?? data_get($event, 'personnel_id')
                    ?? data_get($event, 'employeeId')
                    ?? data_get($event, 'userCode')
                    ?? ''
                ) ?: null,
                'direction' => $direction,
                'event_status' => (string) (
                    data_get($event, 'status')
                    ?? data_get($event, 'result')
                    ?? data_get($event, 'eventStatus')
                    ?? ''
                ) ?: null,
                'occurred_at' => $occurred_at,
                'raw_payload' => $event,
            ];
        }

        return $normalized;
    }

    private function statusFromReason(string $reason): string
    {
        return match ($reason) {
            ZktecoIntegrationException::HOST_UNREACHABLE, ZktecoIntegrationException::SSL_ERROR => ZktecoConnection::STATUS_UNREACHABLE,
            ZktecoIntegrationException::UNSUPPORTED_API => ZktecoConnection::STATUS_UNSUPPORTED,
            default => ZktecoConnection::STATUS_ERROR,
        };
    }
}

