<?php

namespace App\Http\Requests\Agent;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AgentAccessLogsBatchRequest extends FormRequest
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
            'device_id' => ['required', 'integer'],
            'events' => ['required', 'array', 'min:1', 'max:500'],
            'events.*.device_event_uid' => ['required', 'string', 'max:150'],
            'events.*.device_user_id' => ['required', 'string', 'max:100'],
            'events.*.event_timestamp' => ['required', 'date'],
            'events.*.direction' => ['required', Rule::in(['in', 'out', 'unknown'])],
            'events.*.raw_payload' => ['nullable', 'array'],
        ];
    }
}
