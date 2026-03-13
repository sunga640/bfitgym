<?php

namespace App\Http\Controllers\Api\Agent;

use App\Http\Controllers\Controller;
use App\Http\Requests\Agent\AgentCommandsIndexRequest;
use App\Models\AccessControlAgent;
use App\Models\AccessControlCommandAudit;
use App\Models\AccessControlDevice;
use App\Models\AccessControlDeviceCommand;
use App\Support\AccessLogger;
use Illuminate\Support\Facades\DB;

class AgentCommandsController extends Controller
{
    /**
     * GET /api/agent/commands
     *
     * Returns commands for the agent to process.
     *
     * Query params:
     *   - limit: Max commands to return (default 50, max 50)
     *   - peek: If 1/true, return claimable commands without claiming (read-only)
     *
     * Behavior (peek=0, default):
     *   1. Release stale claims (claimed > TTL minutes ago, no finished_at)
     *   2. Resume already-claimed commands by this agent (claimed/processing)
     *   3. If none resumed, claim new pending commands up to limit
     *   4. Return resumed OR newly claimed list
     */
    public function index(AgentCommandsIndexRequest $request)
    {
        /** @var AccessControlAgent $agent */
        $agent = $request->attributes->get('access_control_agent');

        $limit = (int) ($request->validated('limit') ?? 50);
        $limit = max(1, min($limit, 50));

        $peek = $this->isPeekRequest($request);

        $device_ids = $this->getAgentDeviceIds($agent);
        $provider_filters = $agent->providerList();

        $access_logger = app(AccessLogger::class);

        // No assigned devices = nothing to return
        if ($device_ids->isEmpty()) {
            $access_logger->info('agent_commands_polled', [
                'agent_uuid' => $agent->uuid,
                'agent_id' => $agent->id,
                'branch_id' => $agent->branch_id,
                'peek' => $peek,
                'assigned_device_ids' => [],
                'returned_count' => 0,
                'resumed_count' => 0,
                'newly_claimed_count' => 0,
                'stale_released_count' => 0,
            ]);

            return response()->json([
                'server_time' => now()->toIso8601String(),
                'commands' => [],
            ]);
        }

        // Peek mode: return commands without claiming or modifying anything
        if ($peek) {
            $commands = $this->peekCommands($agent, $device_ids, $provider_filters, $limit);

            $access_logger->info('agent_commands_polled', [
                'agent_uuid' => $agent->uuid,
                'agent_id' => $agent->id,
                'branch_id' => $agent->branch_id,
                'peek' => true,
                'assigned_device_ids' => $device_ids->take(10)->toArray(),
                'returned_count' => $commands->count(),
                'command_ids' => $commands->pluck('id')->take(10)->toArray(),
            ]);

            return response()->json([
                'server_time' => now()->toIso8601String(),
                'commands' => $commands->map(fn (AccessControlDeviceCommand $cmd) => $this->formatCommandForAgent($cmd))->values(),
            ]);
        }

        // Normal mode: resume claimed commands, or claim new pending commands (with stale release)
        $result = DB::transaction(function () use ($agent, $device_ids, $provider_filters, $limit, $access_logger) {
            $now = now();

            // Step 1: Release stale claims (configurable TTL, default 2 minutes)
            // Also mark commands as failed if they've exceeded max_attempts
            $stale_ttl_minutes = (int) config('agent.stale_claim_minutes', 2);
            $stale_released_count = 0;
            $stale_released_ids = [];
            $stale_failed_count = 0;
            $stale_failed_ids = [];

            if ($stale_ttl_minutes > 0) {
                $stale_threshold = $now->copy()->subMinutes($stale_ttl_minutes);

                $stale_commands = AccessControlDeviceCommand::query()
                    ->where('branch_id', $agent->branch_id)
                    ->whereIn('access_control_device_id', $device_ids)
                    ->whereIn('provider', $provider_filters)
                    ->where('status', AccessControlDeviceCommand::STATUS_CLAIMED)
                    ->whereNull('finished_at')
                    ->where('claimed_at', '<', $stale_threshold)
                    ->lockForUpdate()
                    ->get();

                // Separate commands that exceeded max_attempts (mark as failed) from those that can retry
                $commands_to_release = [];
                $commands_to_fail = [];

                foreach ($stale_commands as $cmd) {
                    if ($cmd->attempts >= $cmd->max_attempts) {
                        $commands_to_fail[] = $cmd->id;
                    } else {
                        $commands_to_release[] = $cmd->id;
                    }
                }

                // Release commands that can still retry
                if (!empty($commands_to_release)) {
                    $stale_released_ids = $commands_to_release;
                    $stale_released_count = count($stale_released_ids);

                    AccessControlDeviceCommand::query()
                        ->whereIn('id', $stale_released_ids)
                        ->update([
                            'status' => AccessControlDeviceCommand::STATUS_PENDING,
                            'claimed_by_agent_id' => null,
                            'claimed_at' => null,
                            'processing_started_at' => null,
                            'updated_at' => $now,
                        ]);
                }

                // Mark commands that exceeded max_attempts as failed
                if (!empty($commands_to_fail)) {
                    $stale_failed_ids = $commands_to_fail;
                    $stale_failed_count = count($stale_failed_ids);

                    AccessControlDeviceCommand::query()
                        ->whereIn('id', $stale_failed_ids)
                        ->update([
                            'status' => AccessControlDeviceCommand::STATUS_FAILED,
                            'claimed_by_agent_id' => null,
                            'claimed_at' => null,
                            'finished_at' => $now,
                            'last_error' => 'Stale claim - max attempts exceeded',
                            'updated_at' => $now,
                        ]);
                }

                if ($stale_released_count > 0 || $stale_failed_count > 0) {
                    $access_logger->info('stale_claims_processed', [
                        'agent_uuid' => $agent->uuid,
                        'branch_id' => $agent->branch_id,
                        'stale_ttl_minutes' => $stale_ttl_minutes,
                        'released_count' => $stale_released_count,
                        'released_ids' => array_slice($stale_released_ids, 0, 10),
                        'failed_count' => $stale_failed_count,
                        'failed_ids' => array_slice($stale_failed_ids, 0, 10),
                    ]);
                }
            }

            // Step 2: Resume already-claimed commands by this agent
            // Only resume commands that haven't exceeded max_attempts and aren't finished
            $resumed = AccessControlDeviceCommand::query()
                ->where('branch_id', $agent->branch_id)
                ->whereIn('access_control_device_id', $device_ids)
                ->whereIn('provider', $provider_filters)
                ->where('claimed_by_agent_id', $agent->id)
                ->whereIn('status', [
                    AccessControlDeviceCommand::STATUS_CLAIMED,
                    AccessControlDeviceCommand::STATUS_PROCESSING,
                ])
                ->whereNull('finished_at') // Safety: never return finished commands
                ->whereColumn('attempts', '<', 'max_attempts')
                ->where(function ($q) use ($now) {
                    $q->whereNull('available_at')
                        ->orWhere('available_at', '<=', $now);
                })
                ->orderByDesc('priority')
                ->orderBy('created_at')
                ->limit($limit)
                ->get();

            $resumed_count = $resumed->count();
            $resumed_ids = $resumed->pluck('id')->toArray();

            // If we have resumed commands, return them (do NOT claim new ones in same poll).
            if ($resumed_count > 0) {
                return [
                    'commands' => $resumed,
                    'resumed_count' => $resumed_count,
                    'resumed_ids' => $resumed_ids,
                    'newly_claimed_count' => 0,
                    'newly_claimed_ids' => [],
                    'stale_released_count' => $stale_released_count,
                    'stale_released_ids' => $stale_released_ids,
                ];
            }

            // Step 3: Claim new pending commands (only when none resumed)
            $remaining_limit = $limit;
            $newly_claimed = collect();
            $newly_claimed_ids = [];

            if ($remaining_limit > 0) {
                // Get candidate pending commands (excluding any we already resumed)
                $candidates = AccessControlDeviceCommand::query()
                    ->where('branch_id', $agent->branch_id)
                    ->whereIn('access_control_device_id', $device_ids)
                    ->whereIn('provider', $provider_filters)
                    ->where('status', AccessControlDeviceCommand::STATUS_PENDING)
                    ->whereNull('claimed_by_agent_id')
                    ->whereNull('finished_at') // Safety: never return finished commands
                    ->whereColumn('attempts', '<', 'max_attempts')
                    ->where(function ($q) use ($now) {
                        $q->whereNull('available_at')
                            ->orWhere('available_at', '<=', $now);
                    })
                    ->orderByDesc('priority')
                    ->orderBy('created_at')
                    ->limit($remaining_limit)
                    ->lockForUpdate()
                    ->get();

                if ($candidates->isNotEmpty()) {
                    $newly_claimed_ids = $candidates->pluck('id')->toArray();

                    AccessControlDeviceCommand::query()
                        ->whereIn('id', $newly_claimed_ids)
                        ->where('status', AccessControlDeviceCommand::STATUS_PENDING)
                        ->update([
                            'status' => AccessControlDeviceCommand::STATUS_CLAIMED,
                            'claimed_by_agent_id' => $agent->id,
                            'claimed_at' => $now,
                            'updated_at' => $now,
                        ]);

                    foreach ($newly_claimed_ids as $id) {
                        AccessControlCommandAudit::create([
                            'command_id' => $id,
                            'agent_id' => $agent->id,
                            'status' => AccessControlCommandAudit::STATUS_RECEIVED,
                            'message' => null,
                            'result' => null,
                            'created_at' => $now,
                        ]);
                    }

                    // Re-fetch to get updated records
                    $newly_claimed = AccessControlDeviceCommand::query()
                        ->whereIn('id', $newly_claimed_ids)
                        ->orderByDesc('priority')
                        ->orderBy('created_at')
                        ->get();
                }
            }

            return [
                'commands' => $newly_claimed->values(),
                'resumed_count' => $resumed_count,
                'resumed_ids' => $resumed_ids,
                'newly_claimed_count' => count($newly_claimed_ids),
                'newly_claimed_ids' => $newly_claimed_ids,
                'stale_released_count' => $stale_released_count,
                'stale_released_ids' => $stale_released_ids,
            ];
        });

        $commands = $result['commands'];
        $returned_count = $commands->count();

        // Log with detailed counts
        $access_logger->info('agent_commands_polled', [
            'agent_uuid' => $agent->uuid,
            'agent_id' => $agent->id,
            'branch_id' => $agent->branch_id,
            'peek' => false,
            'assigned_device_ids' => $device_ids->take(10)->toArray(),
            'returned_count' => $returned_count,
            'resumed_count' => $result['resumed_count'],
            'newly_claimed_count' => $result['newly_claimed_count'],
            'stale_released_count' => $result['stale_released_count'],
            'command_ids' => $commands->pluck('id')->take(10)->toArray(),
        ]);

        // Log individual claims for newly claimed only
        foreach (array_slice($result['newly_claimed_ids'], 0, 10) as $cmd_id) {
            $cmd = $commands->firstWhere('id', $cmd_id);
            if ($cmd) {
                $access_logger->info('command_claimed_by_agent', [
                    'agent_uuid' => $agent->uuid,
                    'branch_id' => $agent->branch_id,
                    'command_uuid' => $cmd->id,
                    'device_id' => $cmd->access_control_device_id,
                    'command_type' => $cmd->type,
                ]);
            }
        }

        return response()->json([
            'server_time' => now()->toIso8601String(),
            'commands' => $commands->map(fn (AccessControlDeviceCommand $cmd) => $this->formatCommandForAgent($cmd))->values(),
        ]);
    }

    /**
     * Peek at commands without claiming them.
     * Includes:
     * - pending (available now)
     * - claimed by this agent (available now) to support resume visibility
     */
    private function peekCommands(AccessControlAgent $agent, $device_ids, array $provider_filters, int $limit)
    {
        $now = now();

        $pending = AccessControlDeviceCommand::query()
            ->where('branch_id', $agent->branch_id)
            ->whereIn('access_control_device_id', $device_ids)
            ->whereIn('provider', $provider_filters)
            ->where('status', AccessControlDeviceCommand::STATUS_PENDING)
            ->whereNull('finished_at') // Safety: never return finished commands
            ->whereColumn('attempts', '<', 'max_attempts')
            ->where(function ($q) use ($now) {
                $q->whereNull('available_at')
                    ->orWhere('available_at', '<=', $now);
            })
            ->orderByDesc('priority')
            ->orderBy('created_at')
            ->limit($limit)
            ->get();

        $claimed = AccessControlDeviceCommand::query()
            ->where('branch_id', $agent->branch_id)
            ->whereIn('access_control_device_id', $device_ids)
            ->whereIn('provider', $provider_filters)
            ->where('status', AccessControlDeviceCommand::STATUS_CLAIMED)
            ->where('claimed_by_agent_id', $agent->id)
            ->whereNull('finished_at') // Safety: never return finished commands
            ->where(function ($q) use ($now) {
                $q->whereNull('available_at')
                    ->orWhere('available_at', '<=', $now);
            })
            ->orderByDesc('priority')
            ->orderBy('created_at')
            ->limit($limit)
            ->get();

        // Merge + sort by priority desc, created_at asc. No DB changes in peek mode.
        return $pending
            ->merge($claimed)
            ->unique('id')
            ->sortBy(fn (AccessControlDeviceCommand $cmd) => [-(int) $cmd->priority, $cmd->created_at?->getTimestamp() ?? 0])
            ->values()
            ->take($limit);
    }

    /**
     * Check if this is a peek request.
     */
    private function isPeekRequest(AgentCommandsIndexRequest $request): bool
    {
        $peek = $request->input('peek');

        if ($peek === null) {
            return false;
        }

        return in_array($peek, ['1', 'true', 1, true], true);
    }

    /**
     * Format command for API response.
     */
    private function formatCommandForAgent(AccessControlDeviceCommand $cmd): array
    {
        return [
            'id' => $cmd->id,
            'branch_id' => $cmd->branch_id,
            'integration_type' => $cmd->integration_type,
            'provider' => $cmd->provider,
            'device_id' => $cmd->access_control_device_id,
            'type' => $cmd->type,
            'payload' => $cmd->payload,
            'subject_type' => $cmd->subject_type,
            'subject_id' => $cmd->subject_id,
            'priority' => $cmd->priority,
            'attempts' => $cmd->attempts,
            'max_attempts' => $cmd->max_attempts,
            'created_at' => $cmd->created_at?->toIso8601String(),
        ];
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
}
