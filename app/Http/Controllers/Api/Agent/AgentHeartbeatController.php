<?php

namespace App\Http\Controllers\Api\Agent;

use App\Http\Controllers\Controller;
use App\Http\Requests\Agent\AgentHeartbeatRequest;
use App\Models\AccessControlAgent;
use App\Models\AccessControlDevice;
use App\Support\AccessLogger;

class AgentHeartbeatController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(AgentHeartbeatRequest $request)
    {
        /** @var AccessControlAgent $agent */
        $agent = $request->attributes->get('access_control_agent');

        $payload = $request->validated();
        $now = now();

        $device_ids = $this->getAgentDeviceIds($agent);
        $devices = $payload['devices'] ?? [];

        $agent_last_error = collect($devices)
            ->pluck('last_error')
            ->filter(fn($v) => !empty($v))
            ->take(3)
            ->implode(' | ');

        $agent->update([
            'last_seen_at' => $now,
            'last_ip' => $request->ip(),
            'last_error' => $agent_last_error ?: null,
        ]);

        // Update device statuses if provided
        foreach ($devices as $device_status) {
            $device_id = (int) $device_status['device_id'];

            if (!$device_ids->contains($device_id)) {
                continue;
            }

            AccessControlDevice::query()
                ->where('id', $device_id)
                ->where('branch_id', $agent->branch_id)
                ->update([
                    'last_heartbeat_at' => $now,
                    'connection_status' => $device_status['connection_status'],
                    'last_error' => $device_status['last_error'] ?? null,
                    'firmware_version' => $device_status['firmware_version'] ?? null,
                ]);
        }

        // Log heartbeat
        $access_logger = app(AccessLogger::class);
        $access_logger->info('agent_heartbeat', [
            'agent_uuid' => $agent->uuid,
            'agent_id' => $agent->id,
            'branch_id' => $agent->branch_id,
            'client_time' => $payload['client_time'] ?? null,
            'queue_executable' => $payload['queue_executable'] ?? null,
            'queue_pending_upload' => $payload['queue_pending_upload'] ?? null,
            'devices_reported' => count($devices),
        ]);

        return response()->json([
            'ok' => true,
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
}
