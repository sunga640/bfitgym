<?php

namespace App\Http\Controllers\Api\Agent;

use App\Http\Controllers\Controller;
use App\Http\Requests\Agent\AgentAccessLogsBatchRequest;
use App\Models\AccessControlAgent;
use App\Models\AccessControlDevice;
use App\Models\AccessIdentity;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AgentAccessLogsBatchController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(AgentAccessLogsBatchRequest $request)
    {
        /** @var AccessControlAgent $agent */
        $agent = $request->attributes->get('access_control_agent');

        $data = $request->validated();
        $device_id = (int) $data['device_id'];

        $device_ids = $this->getAgentDeviceIds($agent);
        if (!$device_ids->contains($device_id)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $device = AccessControlDevice::query()
            ->where('id', $device_id)
            ->where('branch_id', $agent->branch_id)
            ->first();

        if (!$device) {
            return response()->json(['message' => 'Device not found.'], 404);
        }

        $events = $data['events'];

        $device_user_ids = collect($events)->pluck('device_user_id')->unique()->values();

        $identities = AccessIdentity::query()
            ->where('branch_id', $device->branch_id)
            ->where('integration_type', $device->integration_type)
            ->whereIn('device_user_id', $device_user_ids)
            ->get(['id', 'device_user_id', 'subject_type', 'subject_id'])
            ->keyBy('device_user_id');

        $rows = [];
        $skipped_no_identity = 0;

        $now = now();

        foreach ($events as $event) {
            $identity = $identities->get($event['device_user_id']);
            if (!$identity) {
                $skipped_no_identity++;
                continue;
            }

            $rows[] = [
                'branch_id' => $device->branch_id,
                'integration_type' => $device->integration_type,
                'provider' => $device->provider,
                'access_control_device_id' => $device->id,
                'access_identity_id' => $identity->id,
                'device_event_uid' => $event['device_event_uid'],
                'subject_type' => $identity->subject_type,
                'subject_id' => $identity->subject_id,
                'direction' => $event['direction'],
                'event_timestamp' => Carbon::parse($event['event_timestamp'])->toDateTimeString(),
                'raw_payload' => isset($event['raw_payload']) ? json_encode($event['raw_payload']) : null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (empty($rows)) {
            return response()->json([
                'inserted' => 0,
                'ignored' => count($events),
                'server_time' => $now->toIso8601String(),
            ]);
        }

        $inserted = (int) DB::table('access_logs')->insertOrIgnore($rows);
        $ignored = count($events) - $inserted;

        return response()->json([
            'inserted' => $inserted,
            'ignored' => $ignored,
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
