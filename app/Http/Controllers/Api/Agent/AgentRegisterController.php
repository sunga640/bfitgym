<?php

namespace App\Http\Controllers\Api\Agent;

use App\Http\Controllers\Controller;
use App\Http\Requests\Agent\RegisterAgentRequest;
use App\Models\AccessControlAgent;
use App\Models\AccessControlAgentEnrollment;
use App\Models\AccessControlDevice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AgentRegisterController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(RegisterAgentRequest $request)
    {
        $data = $request->validated();

        $enrollment = AccessControlAgentEnrollment::query()
            ->where('code', $data['enrollment_code'])
            ->first();

        if (!$enrollment) {
            return response()->json(['message' => 'Invalid enrollment code.'], 422);
        }

        if ($enrollment->used_at !== null) {
            return response()->json(['message' => 'Enrollment code already used.'], 422);
        }

        if ($enrollment->expires_at->isPast()) {
            return response()->json(['message' => 'Enrollment code expired.'], 422);
        }

        [$agent, $plaintext_token] = DB::transaction(function () use ($data, $enrollment) {
            $plaintext_token = Str::random(64);

            $agent = AccessControlAgent::create([
                'branch_id' => $enrollment->branch_id,
                'uuid' => (string) Str::uuid(),
                'name' => $data['name'],
                'os' => $data['os'],
                'app_version' => $data['app_version'] ?? null,
                'status' => AccessControlAgent::STATUS_ACTIVE,
                'secret_hash' => hash('sha256', $plaintext_token),
                'last_seen_at' => now(),
                'last_ip' => request()->ip(),
                'last_error' => null,
            ]);

            $enrollment->update([
                'used_at' => now(),
                'used_by_agent_id' => $agent->id,
            ]);

            return [$agent, $plaintext_token];
        });

        $devices = AccessControlDevice::query()
            ->where('branch_id', $agent->branch_id)
            ->get([
                'id',
                'name',
                'serial_number',
                'device_model',
                'device_type',
                'branch_id',
            ]);

        return response()->json([
            'agent_uuid' => $agent->uuid,
            'agent_token' => $plaintext_token, // view-once
            'devices' => $devices,
            'server_time' => now()->toIso8601String(),
        ]);
    }
}
