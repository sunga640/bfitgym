<?php

namespace App\Http\Controllers\Api\Agent;

use App\Http\Controllers\Controller;
use App\Http\Requests\Agent\RegisterAgentRequest;
use App\Models\AccessControlDevice;
use App\Services\AccessControl\AgentEnrollmentService;

class AgentRegisterController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(RegisterAgentRequest $request)
    {
        $data = $request->validated();

        $enrollment_service = app(AgentEnrollmentService::class);
        $enrollment = $enrollment_service->findUsableEnrollmentByCode($data['enrollment_code']);

        if (!$enrollment) {
            return response()->json(['message' => 'Invalid enrollment code.'], 422);
        }

        $result = $enrollment_service->completeEnrollment(
            enrollment: $enrollment,
            agent_name: $data['name'],
            os: $data['os'],
            app_version: $data['app_version'] ?? null,
        );

        $agent = $result['agent'];
        $plaintext_token = $result['token'];

        $agent->update([
            'last_ip' => $request->ip(),
            'last_error' => null,
        ]);

        $devices = AccessControlDevice::query()
            ->where('branch_id', $agent->branch_id)
            ->forIntegration($enrollment->integration_type ?? AccessControlDevice::INTEGRATION_HIKVISION)
            ->when($enrollment->provider, fn($q) => $q->forProvider($enrollment->provider))
            ->get([
                'id',
                'name',
                'serial_number',
                'device_model',
                'device_type',
                'integration_type',
                'provider',
                'branch_id',
            ]);

        return response()->json([
            'agent_uuid' => $agent->uuid,
            'agent_token' => $plaintext_token, // view-once
            'integration_type' => $enrollment->integration_type ?? AccessControlDevice::INTEGRATION_HIKVISION,
            'provider' => $enrollment->provider ?? AccessControlDevice::PROVIDER_HIKVISION_AGENT,
            'devices' => $devices,
            'server_time' => now()->toIso8601String(),
        ]);
    }
}
