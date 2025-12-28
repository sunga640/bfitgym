<?php

namespace App\Http\Controllers\Api\Agent;

use App\Http\Controllers\Controller;
use App\Http\Requests\Agent\AgentCommandResultRequest;
use App\Models\AccessControlAgent;
use App\Models\AccessControlCommandAudit;
use App\Models\AccessControlDevice;
use App\Models\AccessControlDeviceCommand;
use App\Models\AccessIdentity;
use App\Support\AccessLogger;
use Illuminate\Support\Facades\DB;

class AgentCommandResultController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(AgentCommandResultRequest $request, string $command)
    {
        /** @var AccessControlAgent $agent */
        $agent = $request->attributes->get('access_control_agent');

        $cmd = AccessControlDeviceCommand::query()
            ->where('id', $command)
            ->where('branch_id', $agent->branch_id)
            ->first();

        if (! $cmd) {
            // Command no longer exists in cloud (may have been deleted/cleaned up).
            // Return 200 with a flag so the agent can clean up its local record.
            $access_logger = app(AccessLogger::class);
            $access_logger->warning('command_result_for_missing_command', [
                'agent_uuid' => $agent->uuid,
                'command_id' => $command,
                'branch_id' => $agent->branch_id,
                'result_status' => $request->input('status'),
            ]);

            return response()->json([
                'ok' => true,
                'acknowledged' => true,
                'command_found' => false,
                'message' => 'Command no longer exists, result acknowledged for cleanup.',
                'server_time' => now()->toIso8601String(),
            ], 200);
        }

        $device_ids = $this->getAgentDeviceIds($agent);
        if (! $device_ids->contains($cmd->access_control_device_id)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $data = $request->validated();
        $now = now();

        $error_message = $data['error_message'] ?? $data['error'] ?? null;
        $error_message = is_string($error_message) ? trim($error_message) : null;
        $error_message = $error_message !== '' ? $error_message : null;

        $transition_error = null;
        $already_completed = false;

        DB::transaction(function () use ($agent, $cmd, $data, $now, &$transition_error, &$already_completed) {
            /** @var AccessControlDeviceCommand|null $locked */
            $locked = AccessControlDeviceCommand::query()
                ->where('id', $cmd->id)
                ->where('branch_id', $agent->branch_id)
                ->lockForUpdate()
                ->first();

            if (! $locked) {
                $transition_error = 'Command not found.';
                return;
            }

            // Idempotency: if already finished, accept duplicate results without changing state.
            if (in_array($locked->status, [
                AccessControlDeviceCommand::STATUS_DONE,
                AccessControlDeviceCommand::STATUS_FAILED,
                AccessControlDeviceCommand::STATUS_CANCELLED,
                AccessControlDeviceCommand::STATUS_SUPERSEDED,
            ], true)) {
                $already_completed = true;
                return;
            }

            // Accept results for pending, claimed, or processing commands.
            // Pending is included because stale claim release may have reset the status,
            // but the agent legitimately completed the work and is reporting the result.
            if (! in_array($locked->status, [
                AccessControlDeviceCommand::STATUS_PENDING,
                AccessControlDeviceCommand::STATUS_CLAIMED,
                AccessControlDeviceCommand::STATUS_PROCESSING,
            ], true)) {
                $transition_error = 'Command is not in a valid state for result submission.';
                return;
            }

            // Safety: if command is claimed by a DIFFERENT agent, reject.
            // But allow if claimed_by_agent_id is null (stale release) or matches this agent.
            if ($locked->claimed_by_agent_id !== null && (int) $locked->claimed_by_agent_id !== (int) $agent->id) {
                $transition_error = 'Command is claimed by another agent.';
                return;
            }

            $status = $data['status'];

            AccessControlCommandAudit::create([
                'command_id' => $locked->id,
                'agent_id' => $agent->id,
                'status' => $status === 'done' ? AccessControlCommandAudit::STATUS_DONE : AccessControlCommandAudit::STATUS_FAILED,
                'message' => $data['error_message'] ?? $data['error'] ?? null,
                'result' => $data['result'] ?? null,
                'created_at' => $now,
            ]);

            if ($status === 'done') {
                $locked->update([
                    'status' => AccessControlDeviceCommand::STATUS_DONE,
                    'finished_at' => $now,
                    'last_error' => null,
                    // Must stop being "claimed"
                    'claimed_by_agent_id' => null,
                    'claimed_at' => null,
                ]);

                // Handle person_upsert success - update AccessIdentity if this was a user sync
                if ($locked->type === AccessControlDeviceCommand::TYPE_PERSON_UPSERT) {
                    $this->handleUserSyncSuccess($locked, $data, $now);
                }

                return;
            }

            // failed
            $attempts = (int) $locked->attempts + 1;
            $max_attempts = (int) $locked->max_attempts;
            $error = $data['error_message'] ?? $data['error'] ?? 'Command failed';

            if ($attempts < $max_attempts) {
                $backoff_minutes = min(60, (int) pow(2, min($attempts, 6))); // 2,4,8,... capped

                $locked->update([
                    'status' => AccessControlDeviceCommand::STATUS_PENDING,
                    'attempts' => $attempts,
                    'last_error' => $error,
                    'available_at' => $now->copy()->addMinutes($backoff_minutes),
                    // Must stop being "claimed"
                    'claimed_by_agent_id' => null,
                    'claimed_at' => null,
                    'processing_started_at' => null,
                ]);

                return;
            }

            $locked->update([
                'status' => AccessControlDeviceCommand::STATUS_FAILED,
                'attempts' => $attempts,
                'last_error' => $error,
                'finished_at' => $now,
                // Must stop being "claimed"
                'claimed_by_agent_id' => null,
                'claimed_at' => null,
            ]);

            // Handle person_upsert failure - update AccessIdentity with error if this was a user sync
            if ($locked->type === AccessControlDeviceCommand::TYPE_PERSON_UPSERT) {
                $this->handleUserSyncFailure($locked, $error, $now);
            }
        });

        if ($transition_error !== null) {
            return response()->json(['message' => $transition_error], 409);
        }

        $access_logger = app(AccessLogger::class);
        $context = [
            'agent_uuid' => $agent->uuid,
            'command_uuid' => $cmd->id,
            'device_id' => $cmd->access_control_device_id,
            'branch_id' => $cmd->branch_id,
            'member_id' => $cmd->subject_type === 'member' ? $cmd->subject_id : null,
            'command_type' => $cmd->type,
            'valid_to' => is_array($cmd->payload) ? ($cmd->payload['valid_to'] ?? null) : null,
            'error_message' => $error_message,
            'already_completed' => $already_completed,
        ];

        if ($already_completed) {
            $access_logger->info('command_result_already_completed', $context);

            return response()->json([
                'ok' => true,
                'acknowledged' => true,
                'already_completed' => true,
                'server_time' => $now->toIso8601String(),
            ]);
        }

        if (($data['status'] ?? null) === 'done') {
            $access_logger->info('command_result_received', $context);
        } else {
            $access_logger->error('command_result_failed', $context);
        }

        return response()->json([
            'ok' => true,
            'acknowledged' => true,
            'already_completed' => false,
            'server_time' => $now->toIso8601String(),
        ]);
    }

    /**
     * @return \Illuminate\Support\Collection<int, int>
     */
    protected function getAgentDeviceIds(AccessControlAgent $agent)
    {
        $pivot_ids = $agent->devices()->pluck('access_control_devices.id');

        $primary_ids = AccessControlDevice::query()
            ->where('branch_id', $agent->branch_id)
            ->where('access_control_agent_id', $agent->id)
            ->pluck('id');

        return $pivot_ids->merge($primary_ids)->unique()->values();
    }

    /**
     * Handle successful user sync (person_upsert).
     * Updates AccessIdentity to mark as synced to device.
     */
    protected function handleUserSyncSuccess(AccessControlDeviceCommand $command, array $data, $now): void
    {
        $access_logger = app(AccessLogger::class);

        $payload = is_array($command->payload) ? $command->payload : [];
        $access_identity_id = $payload['access_identity_id'] ?? null;

        // Only process if this command has an access_identity_id (user sync from button)
        if (!$access_identity_id) {
            // This is a regular person_upsert without identity tracking - skip
            return;
        }

        $identity = AccessIdentity::find($access_identity_id);
        if (!$identity) {
            $access_logger->warning('user_sync_success_identity_not_found', [
                'command_id' => $command->id,
                'access_identity_id' => $access_identity_id,
            ]);
            return;
        }

        $identity->update([
            'is_active' => true,
            'device_synced_at' => $now,
            'last_sync_error' => null,
        ]);

        $access_logger->info('user_sync_success', [
            'command_id' => $command->id,
            'access_identity_id' => $identity->id,
            'member_id' => $identity->subject_type === 'member' ? $identity->subject_id : null,
            'device_user_id' => $identity->device_user_id,
            'synced_at' => $now->toIso8601String(),
        ]);
    }

    /**
     * Handle failed user sync (person_upsert).
     * Updates AccessIdentity with error message.
     */
    protected function handleUserSyncFailure(AccessControlDeviceCommand $command, string $error, $now): void
    {
        $access_logger = app(AccessLogger::class);

        $payload = is_array($command->payload) ? $command->payload : [];
        $access_identity_id = $payload['access_identity_id'] ?? null;

        // Only process if this command has an access_identity_id (user sync from button)
        if (!$access_identity_id) {
            // This is a regular person_upsert without identity tracking - skip
            return;
        }

        $identity = AccessIdentity::find($access_identity_id);
        if (!$identity) {
            $access_logger->warning('user_sync_failure_identity_not_found', [
                'command_id' => $command->id,
                'access_identity_id' => $access_identity_id,
            ]);
            return;
        }

        // Keep is_active as is, set error message
        $identity->update([
            'last_sync_error' => substr($error, 0, 500),
        ]);

        $access_logger->error('user_sync_failed', [
            'command_id' => $command->id,
            'access_identity_id' => $identity->id,
            'member_id' => $identity->subject_type === 'member' ? $identity->subject_id : null,
            'device_user_id' => $identity->device_user_id,
            'error' => $error,
        ]);
    }
}
