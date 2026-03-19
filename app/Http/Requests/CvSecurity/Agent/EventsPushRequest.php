<?php

namespace App\Http\Requests\CvSecurity\Agent;

use Illuminate\Foundation\Http\FormRequest;

class EventsPushRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'events' => ['required', 'array', 'max:1000'],
            'events.*.external_event_id' => ['nullable', 'string', 'max:120'],
            'events.*.external_person_id' => ['nullable', 'string', 'max:120'],
            'events.*.event_type' => ['nullable', 'string', 'max:80'],
            'events.*.direction' => ['nullable', 'string', 'max:20'],
            'events.*.occurred_at' => ['required', 'date'],
            'events.*.device_id' => ['nullable', 'string', 'max:120'],
            'events.*.door_id' => ['nullable', 'string', 'max:120'],
            'events.*.reader_id' => ['nullable', 'string', 'max:120'],
            'events.*.raw_payload' => ['nullable', 'array'],
        ];
    }
}

