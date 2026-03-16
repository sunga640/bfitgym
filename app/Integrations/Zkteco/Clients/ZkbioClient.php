<?php

namespace App\Integrations\Zkteco\Clients;

use App\Integrations\Zkteco\Exceptions\ZktecoIntegrationException;
use App\Models\ZktecoConnection;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

class ZkbioClient
{
    /**
     * @var array<int, string>
     */
    private array $tokens = [];

    /**
     * @return array<string, mixed>
     */
    public function health(ZktecoConnection $connection): array
    {
        $response = $this->send($connection, 'GET', config('zkteco.endpoints.health'));

        return $response->json() ?: [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function devices(ZktecoConnection $connection): array
    {
        $response = $this->send($connection, 'GET', config('zkteco.endpoints.devices'));

        return $this->extractList($response->json(), ['data', 'devices', 'results']);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function upsertPersonnel(ZktecoConnection $connection, array $payload): array
    {
        $response = $this->send($connection, 'POST', config('zkteco.endpoints.personnel_upsert'), payload: $payload);

        return $response->json() ?: [];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function syncAccess(ZktecoConnection $connection, array $payload): array
    {
        $response = $this->send($connection, 'POST', config('zkteco.endpoints.access_sync'), payload: $payload);

        return $response->json() ?: [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function events(ZktecoConnection $connection, ?Carbon $since = null, ?Carbon $until = null): array
    {
        $query = [];
        if ($since) {
            $query['since'] = $since->toIso8601String();
        }
        if ($until) {
            $query['until'] = $until->toIso8601String();
        }

        $response = $this->send($connection, 'GET', config('zkteco.endpoints.events'), query: $query);

        return $this->extractList($response->json(), ['data', 'events', 'results']);
    }

    private function send(
        ZktecoConnection $connection,
        string $method,
        string $endpoint,
        array $query = [],
        array $payload = [],
        bool $authenticate = true
    ): Response {
        try {
            $request = $this->baseRequest($connection);

            if ($authenticate) {
                $request = $request->withHeaders($this->authHeaders($connection));
            }

            $options = [];
            if (!empty($query)) {
                $options['query'] = $query;
            }

            if (!empty($payload)) {
                $options['json'] = $payload;
            }

            $response = $request->send($method, $endpoint, $options);
        } catch (ConnectionException $e) {
            $message = strtolower($e->getMessage());

            if (str_contains($message, 'ssl') || str_contains($message, 'certificate')) {
                throw ZktecoIntegrationException::sslError();
            }

            throw ZktecoIntegrationException::hostUnreachable();
        } catch (\Throwable $e) {
            throw ZktecoIntegrationException::unknown($e->getMessage());
        }

        return $this->guardResponse($response, $endpoint);
    }

    /**
     * @return array<string, string>
     */
    private function authHeaders(ZktecoConnection $connection): array
    {
        if (!empty($connection->api_key)) {
            return [
                'X-API-Key' => (string) $connection->api_key,
            ];
        }

        if (empty($connection->username) || empty($connection->password)) {
            return [];
        }

        return [
            'Authorization' => 'Bearer ' . $this->token($connection),
        ];
    }

    private function token(ZktecoConnection $connection): string
    {
        if (isset($this->tokens[$connection->id])) {
            return $this->tokens[$connection->id];
        }

        $payload = [
            'username' => $connection->username,
            'password' => $connection->password,
        ];

        $response = $this->send(
            $connection,
            'POST',
            config('zkteco.endpoints.auth'),
            payload: $payload,
            authenticate: false
        );

        $token = (string) (
            data_get($response->json(), 'token')
            ?? data_get($response->json(), 'access_token')
            ?? data_get($response->json(), 'data.token')
            ?? ''
        );

        if ($token === '') {
            throw ZktecoIntegrationException::unsupportedApi('ZKBio auth response did not include an API token.');
        }

        $this->tokens[$connection->id] = $token;

        return $token;
    }

    private function baseRequest(ZktecoConnection $connection): PendingRequest
    {
        $retry_times = (int) config('zkteco.http.retry_times', 2);
        $retry_sleep = (int) config('zkteco.http.retry_sleep_ms', 300);

        $request = Http::acceptJson()
            ->asJson()
            ->baseUrl($connection->resolvedBaseUrl())
            ->timeout(max(1, (int) $connection->timeout_seconds))
            ->retry($retry_times, $retry_sleep, function ($exception, PendingRequest $pending) {
                return $exception instanceof ConnectionException;
            }, throw: false);

        if ($connection->allow_self_signed) {
            $request = $request->withOptions(['verify' => false]);
        }

        return $request;
    }

    private function guardResponse(Response $response, string $endpoint): Response
    {
        if ($response->successful()) {
            return $response;
        }

        $status = $response->status();

        if (in_array($status, [401, 403], true)) {
            throw ZktecoIntegrationException::invalidCredentials();
        }

        if ($status === 429) {
            throw ZktecoIntegrationException::rateLimited();
        }

        if (in_array($status, [404, 405, 501], true)) {
            throw ZktecoIntegrationException::unsupportedApi(
                "Endpoint {$endpoint} is not available on this ZKBio installation."
            );
        }

        if ($status >= 500) {
            throw ZktecoIntegrationException::remoteError('ZKBio API temporary error.', [
                'status' => $status,
            ]);
        }

        throw ZktecoIntegrationException::remoteError('ZKBio API rejected the request.', [
            'status' => $status,
            'body' => $response->json() ?: $response->body(),
        ]);
    }

    /**
     * @param  mixed  $payload
     * @param  array<int, string>  $candidate_keys
     * @return array<int, array<string, mixed>>
     */
    private function extractList(mixed $payload, array $candidate_keys): array
    {
        if (is_array($payload) && array_is_list($payload)) {
            return array_values(array_filter($payload, 'is_array'));
        }

        if (!is_array($payload)) {
            return [];
        }

        foreach ($candidate_keys as $key) {
            $value = data_get($payload, $key);

            if (is_array($value) && array_is_list($value)) {
                return array_values(array_filter($value, 'is_array'));
            }
        }

        return [];
    }
}

