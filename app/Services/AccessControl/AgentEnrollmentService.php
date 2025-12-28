<?php

namespace App\Services\AccessControl;

use App\Models\AccessControlAgent;
use App\Models\AccessControlAgentEnrollment;
use App\Models\AccessControlDevice;
use App\Models\Branch;
use App\Models\User;
use App\Support\AccessLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AgentEnrollmentService
{
    public function __construct(
        protected AccessLogger $logger
    ) {}

    /**
     * Create a new agent enrollment.
     *
     * @param Branch $branch The branch for this enrollment
     * @param User $actor The user creating the enrollment
     * @param array $device_ids Device IDs to pre-assign to the agent
     * @param string|null $label Optional label/notes for the enrollment
     * @param int $expires_in_minutes Minutes until enrollment expires (default 30)
     *
     * @return array{enrollment: AccessControlAgentEnrollment, plaintext_code: string, agent_uuid: string}
     */
    public function createEnrollment(
        Branch $branch,
        User $actor,
        array $device_ids = [],
        ?string $label = null,
        int $expires_in_minutes = 30
    ): array {
        // Validate devices belong to the same branch
        $valid_device_ids = $this->validateDeviceIds($device_ids, $branch->id);

        return DB::transaction(function () use ($branch, $actor, $valid_device_ids, $label, $expires_in_minutes) {
            // Generate random enrollment code (64 chars)
            $plaintext_code = Str::random(64);
            $code_hash = hash('sha256', $plaintext_code);

            // Pre-create the agent with placeholder values
            $agent_uuid = (string) Str::uuid();
            $agent = AccessControlAgent::create([
                'branch_id' => $branch->id,
                'uuid' => $agent_uuid,
                'name' => 'Agent-' . substr($agent_uuid, 0, 8),
                'os' => 'windows',
                'status' => AccessControlAgent::STATUS_ACTIVE,
                'secret_hash' => '', // Will be set during registration
            ]);

            // Create enrollment
            $enrollment = AccessControlAgentEnrollment::create([
                'branch_id' => $branch->id,
                'access_control_agent_id' => $agent->id,
                'code' => $plaintext_code, // Store plaintext for lookup during registration (legacy support)
                'code_hash' => $code_hash,
                'status' => AccessControlAgentEnrollment::STATUS_ACTIVE,
                'label' => $label,
                'expires_at' => now()->addMinutes($expires_in_minutes),
                'created_by' => $actor->id,
            ]);

            // Attach pre-assigned devices
            if (!empty($valid_device_ids)) {
                $enrollment->devices()->attach($valid_device_ids);

                // Also assign devices to the agent
                $sync_payload = [];
                foreach ($valid_device_ids as $device_id) {
                    $sync_payload[$device_id] = ['branch_id' => $branch->id];
                }
                $agent->devices()->sync($sync_payload);
            }

            $this->logger->info('enrollment_created', [
                'enrollment_id' => $enrollment->id,
                'agent_id' => $agent->id,
                'agent_uuid' => $agent_uuid,
                'branch_id' => $branch->id,
                'actor_user_id' => $actor->id,
                'device_ids' => $valid_device_ids,
                'expires_at' => $enrollment->expires_at->toIso8601String(),
            ]);

            return [
                'enrollment' => $enrollment,
                'plaintext_code' => $plaintext_code,
                'agent_uuid' => $agent_uuid,
            ];
        });
    }

    /**
     * Revoke an enrollment code.
     */
    public function revokeEnrollment(AccessControlAgentEnrollment $enrollment, User $actor): void
    {
        if ($enrollment->status === AccessControlAgentEnrollment::STATUS_USED) {
            throw new \InvalidArgumentException('Cannot revoke an already used enrollment.');
        }

        $enrollment->revoke();

        // If the agent was pre-created but never activated, revoke it too
        if ($enrollment->agent && !$enrollment->used_by_agent_id) {
            $enrollment->agent->revoke();
        }

        $this->logger->info('enrollment_revoked', [
            'enrollment_id' => $enrollment->id,
            'branch_id' => $enrollment->branch_id,
            'actor_user_id' => $actor->id,
        ]);
    }

    /**
     * Find an enrollment by code (for agent registration).
     */
    public function findUsableEnrollmentByCode(string $code): ?AccessControlAgentEnrollment
    {
        // Try lookup by hash first, then by plaintext for backward compatibility
        $code_hash = hash('sha256', $code);

        $enrollment = AccessControlAgentEnrollment::query()
            ->where(function ($q) use ($code, $code_hash) {
                $q->where('code_hash', $code_hash)
                    ->orWhere('code', $code);
            })
            ->usable()
            ->first();

        return $enrollment;
    }

    /**
     * Complete enrollment - mark as used and return agent token.
     *
     * @return array{agent: AccessControlAgent, token: string}
     */
    public function completeEnrollment(
        AccessControlAgentEnrollment $enrollment,
        string $agent_name,
        string $os = 'windows',
        ?string $app_version = null
    ): array {
        if (!$enrollment->isUsable()) {
            throw new \InvalidArgumentException('Enrollment is not usable (expired or already used).');
        }

        return DB::transaction(function () use ($enrollment, $agent_name, $os, $app_version) {
            $agent = $enrollment->agent;

            if (!$agent) {
                throw new \RuntimeException('Pre-created agent not found for enrollment.');
            }

            // Generate new secure token
            $plaintext_token = Str::random(64);
            $token_hash = hash('sha256', $plaintext_token);

            // Update agent with registration details
            $agent->update([
                'name' => $agent_name,
                'os' => $os,
                'app_version' => $app_version,
                'secret_hash' => $token_hash,
                'last_seen_at' => now(),
            ]);

            // Mark enrollment as used
            $enrollment->markUsed($agent);

            $this->logger->info('enrollment_completed', [
                'enrollment_id' => $enrollment->id,
                'agent_id' => $agent->id,
                'agent_uuid' => $agent->uuid,
                'branch_id' => $enrollment->branch_id,
                'agent_name' => $agent_name,
            ]);

            return [
                'agent' => $agent->fresh(),
                'token' => $plaintext_token,
            ];
        });
    }

    /**
     * Update expired enrollments status.
     */
    public function markExpiredEnrollments(): int
    {
        return AccessControlAgentEnrollment::query()
            ->where('status', AccessControlAgentEnrollment::STATUS_ACTIVE)
            ->where('expires_at', '<', now())
            ->update(['status' => AccessControlAgentEnrollment::STATUS_EXPIRED]);
    }

    /**
     * Validate that device IDs belong to the specified branch.
     */
    protected function validateDeviceIds(array $device_ids, int $branch_id): array
    {
        if (empty($device_ids)) {
            return [];
        }

        return AccessControlDevice::query()
            ->where('branch_id', $branch_id)
            ->whereIn('id', $device_ids)
            ->pluck('id')
            ->all();
    }
}
