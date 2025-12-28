<?php

namespace App\Http\Requests\Agent;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AgentHeartbeatRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Basic heartbeat info (all optional for minimal heartbeats)
            'client_time' => ['nullable', 'string', 'max:50'],
            'app_version' => ['nullable', 'string', 'max:50'],
            'os' => ['nullable', 'string', 'max:100'],

            // Queue status (optional but useful)
            'queue_executable' => ['nullable', 'integer', 'min:0'],
            'queue_pending_upload' => ['nullable', 'integer', 'min:0'],

            // Device status array (optional - empty array is fine)
            'devices' => ['nullable', 'array', 'max:200'],
            'devices.*.device_id' => ['required_with:devices', 'integer'],
            'devices.*.connection_status' => ['required_with:devices', Rule::in(['online', 'offline', 'unknown'])],
            'devices.*.last_error' => ['nullable', 'string', 'max:5000'],
            'devices.*.firmware_version' => ['nullable', 'string', 'max:50'],
        ];
    }
}
