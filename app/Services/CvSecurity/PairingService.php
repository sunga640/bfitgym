<?php

namespace App\Services\CvSecurity;

use App\Models\CvSecurityAgent;
use App\Models\CvSecurityConnection;
use App\Models\CvSecurityPairingToken;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class PairingService
{
    public function __construct(
        private readonly ActivityLogger $activity_logger,
    ) {
    }

    /**
     * @return array{token_model:CvSecurityPairingToken, plaintext_token:string}
     */
    public function generateToken(CvSecurityConnection $connection, ?User $actor = null, int $ttl_minutes = 30): array
    {
        return DB::transaction(function () use ($connection, $actor, $ttl_minutes) {
            $raw = 'CVP-' . Str::upper(Str::random(12));
            $hash = hash('sha256', $raw);

            $token = CvSecurityPairingToken::query()->create([
                'cvsecurity_connection_id' => $connection->id,
                'branch_id' => $connection->branch_id,
                'created_by' => $actor?->id,
                'token_hash' => $hash,
                'token_hint' => substr($raw, -6),
                'expires_at' => now()->addMinutes(max(1, $ttl_minutes)),
            ]);

            $connection->update([
                'pairing_status' => CvSecurityConnection::PAIRING_TOKEN_ISSUED,
                'status' => CvSecurityConnection::STATUS_PENDING,
                'updated_by' => $actor?->id,
            ]);

            $this->activity_logger->log(
                connection: $connection,
                level: 'info',
                event: 'pairing_token_generated',
                message: 'Pairing token generated for local agent.',
                context: [
                    'token_id' => $token->id,
                    'expires_at' => $token->expires_at?->toIso8601String(),
                ],
            );

            return [
                'token_model' => $token,
                'plaintext_token' => $raw,
            ];
        });
    }

    /**
     * @param array<string, mixed> $agent_payload
     * @param array<string, mixed> $connection_overrides
     * @return array{connection:CvSecurityConnection,agent:CvSecurityAgent,agent_token:string}
     */
    public function claim(
        string $pairing_token,
        array $agent_payload,
        array $connection_overrides = [],
        ?string $ip = null,
    ): array {
        $hash = hash('sha256', trim($pairing_token));

        /** @var CvSecurityPairingToken|null $token */
        $token = CvSecurityPairingToken::query()
            ->where('token_hash', $hash)
            ->first();

        if (!$token || !$token->isUsable()) {
            throw new \InvalidArgumentException('Pairing token is invalid or expired.');
        }

        return DB::transaction(function () use ($token, $agent_payload, $connection_overrides, $ip) {
            $connection = CvSecurityConnection::query()->lockForUpdate()->findOrFail($token->cvsecurity_connection_id);

            $agent_uuid = (string) ($agent_payload['agent_uuid'] ?? Str::uuid());
            $agent_name = trim((string) ($agent_payload['agent_name'] ?? 'Local Agent'));
            $app_version = trim((string) ($agent_payload['app_version'] ?? ''));
            $os = trim((string) ($agent_payload['os'] ?? ''));

            $plaintext_auth_token = Str::random(64);
            $agent_payload_for_save = [
                'cvsecurity_connection_id' => $connection->id,
                'branch_id' => $connection->branch_id,
                'uuid' => $agent_uuid,
                'display_name' => $agent_name !== '' ? $agent_name : 'Local Agent',
                'status' => CvSecurityAgent::STATUS_ACTIVE,
                'os' => $os !== '' ? $os : null,
                'app_version' => $app_version !== '' ? $app_version : null,
                'last_ip' => $ip,
                'auth_token_hash' => hash('sha256', $plaintext_auth_token),
                'auth_token_encrypted' => Crypt::encryptString($plaintext_auth_token),
                'paired_at' => now(),
                'last_seen_at' => now(),
                'last_heartbeat_at' => now(),
            ];

            /** @var CvSecurityAgent|null $existing_by_uuid */
            $existing_by_uuid = CvSecurityAgent::query()
                ->where('uuid', $agent_uuid)
                ->lockForUpdate()
                ->first();

            $reused_existing_agent = false;
            $previous_connection_id = null;

            if ($existing_by_uuid) {
                $reused_existing_agent = true;
                $previous_connection_id = $existing_by_uuid->cvsecurity_connection_id;
                $existing_by_uuid->fill($agent_payload_for_save)->save();
                $agent = $existing_by_uuid;
            } else {
                try {
                    $agent = CvSecurityAgent::query()->create($agent_payload_for_save);
                } catch (UniqueConstraintViolationException $e) {
                    // Last-resort race-safety: if another request created the same UUID,
                    // reuse that row instead of failing pairing with HTTP 500.
                    $existing_after_race = CvSecurityAgent::query()
                        ->where('uuid', $agent_uuid)
                        ->lockForUpdate()
                        ->first();

                    if (!$existing_after_race) {
                        throw $e;
                    }

                    $reused_existing_agent = true;
                    $previous_connection_id = $existing_after_race->cvsecurity_connection_id;
                    $existing_after_race->fill($agent_payload_for_save)->save();
                    $agent = $existing_after_race;
                }
            }

            if ($reused_existing_agent
                && $previous_connection_id
                && (int) $previous_connection_id !== (int) $connection->id) {
                CvSecurityConnection::query()
                    ->where('id', $previous_connection_id)
                    ->update([
                        'agent_status' => 'offline',
                        'status' => CvSecurityConnection::STATUS_DISCONNECTED,
                        'last_error' => 'Agent UUID was re-paired to another connection.',
                        'last_error_at' => now(),
                    ]);
            }

            $update_data = [
                'pairing_status' => CvSecurityConnection::PAIRING_PAIRED,
                'status' => CvSecurityConnection::STATUS_PAIRED,
                'agent_status' => 'online',
                'agent_label' => $agent->display_name,
                'last_heartbeat_at' => now(),
            ];

            if (!empty($connection_overrides['cv_base_url'])) {
                $update_data['cv_base_url'] = trim((string) $connection_overrides['cv_base_url']);
            }
            if (!empty($connection_overrides['cv_port'])) {
                $update_data['cv_port'] = (int) $connection_overrides['cv_port'];
            }
            if (array_key_exists('cv_username', $connection_overrides)) {
                $username = trim((string) $connection_overrides['cv_username']);
                $update_data['cv_username'] = $username !== '' ? $username : null;
            }
            if (!empty($connection_overrides['cv_password'])) {
                $update_data['cv_password'] = (string) $connection_overrides['cv_password'];
            }
            if (!empty($connection_overrides['cv_api_token'])) {
                $update_data['cv_api_token'] = (string) $connection_overrides['cv_api_token'];
            }
            if (!empty($connection_overrides['poll_interval_seconds'])) {
                $update_data['poll_interval_seconds'] = max(5, (int) $connection_overrides['poll_interval_seconds']);
            }
            if (!empty($connection_overrides['timezone'])) {
                $update_data['timezone'] = trim((string) $connection_overrides['timezone']);
            }

            $connection->update($update_data);

            $token->update([
                'claimed_at' => now(),
                'claimed_by_agent_id' => $agent->id,
            ]);

            $this->activity_logger->log(
                connection: $connection,
                level: 'info',
                event: 'agent_paired',
                message: 'Local agent paired successfully.',
                context: [
                    'agent_id' => $agent->id,
                    'agent_uuid' => $agent->uuid,
                    'reused_existing_agent' => $reused_existing_agent,
                    'previous_connection_id' => $previous_connection_id,
                ],
                agent: $agent,
            );

            return [
                'connection' => $connection->fresh(),
                'agent' => $agent,
                'agent_token' => $plaintext_auth_token,
            ];
        });
    }
}
