<?php

namespace App\Http\Requests\CvSecurity\Agent;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StatusReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cvsecurity_status' => ['required', Rule::in(['reachable', 'unreachable', 'unknown'])],
            'last_error' => ['nullable', 'string', 'max:2000'],
            'ack_test_connection' => ['nullable', 'boolean'],
            'ack_sync_members' => ['nullable', 'boolean'],
            'ack_pull_events' => ['nullable', 'boolean'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}

