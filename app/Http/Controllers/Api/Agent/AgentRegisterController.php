<?php

namespace App\Http\Controllers\Api\Agent;

use App\Http\Controllers\Controller;
use App\Http\Requests\Agent\RegisterAgentRequest;
use App\Models\AccessControlAgentEnrollment;
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
            return response()->json(['message' => $this->resolveEnrollmentFailureMessage($data['enrollment_code'])], 422);
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
            ->when(
                $enrollment->provider,
                fn($q) => $q->whereIn('provider', AccessControlDevice::providerAliases((string) $enrollment->provider))
            )
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

        $devices = $devices->map(function (AccessControlDevice $device) {
            return [
                'id' => $device->id,
                'name' => $device->name,
                'serial_number' => $device->serial_number,
                'device_model' => $device->device_model,
                'device_type' => $device->device_type,
                'integration_type' => $device->integration_type,
                'provider' => $device->provider,
                'driver' => AccessControlDevice::driverForProvider($device->provider),
                'branch_id' => $device->branch_id,
            ];
        })->values();

        return response()->json([
            'agent_uuid' => $agent->uuid,
            'agent_token' => $plaintext_token, // view-once
            'integration_type' => $enrollment->integration_type ?? AccessControlDevice::INTEGRATION_HIKVISION,
            'provider' => $enrollment->provider ?? AccessControlDevice::PROVIDER_HIKVISION_AGENT,
            'driver' => AccessControlDevice::driverForProvider($enrollment->provider),
            'devices' => $devices,
            'server_time' => now()->toIso8601String(),
        ]);
    }

    private function resolveEnrollmentFailureMessage(string $code): string
    {
        $code_hash = hash('sha256', $code);

        $enrollment = AccessControlAgentEnrollment::query()
            ->where(function ($query) use ($code, $code_hash) {
                $query->where('code_hash', $code_hash)
                    ->orWhere('code', $code);
            })
            ->first();

        if (!$enrollment) {
            return 'Invalid enrollment code.';
        }

        if ($enrollment->status === AccessControlAgentEnrollment::STATUS_USED) {
            return 'Enrollment code already used. Generate a new code from cloud.';
        }

        if ($enrollment->status === AccessControlAgentEnrollment::STATUS_REVOKED) {
            return 'Enrollment code was revoked. Generate a new code from cloud.';
        }

        if ($enrollment->isExpired() || $enrollment->status === AccessControlAgentEnrollment::STATUS_EXPIRED) {
            return 'Enrollment code expired. Generate a new code from cloud.';
        }

        return 'Enrollment code is not active. Generate a new code from cloud.';
    }
}
