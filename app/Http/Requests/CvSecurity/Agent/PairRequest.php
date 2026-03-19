<?php

namespace App\Http\Requests\CvSecurity\Agent;

use Illuminate\Foundation\Http\FormRequest;

class PairRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pairing_token' => ['required', 'string', 'max:100'],
            'agent_uuid' => ['nullable', 'uuid'],
            'agent_name' => ['required', 'string', 'max:150'],
            'os' => ['nullable', 'string', 'max:40'],
            'app_version' => ['nullable', 'string', 'max:40'],

            'cv_base_url' => ['nullable', 'string', 'max:255'],
            'cv_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'cv_username' => ['nullable', 'string', 'max:120'],
            'cv_password' => ['nullable', 'string', 'max:255'],
            'cv_api_token' => ['nullable', 'string', 'max:255'],
            'poll_interval_seconds' => ['nullable', 'integer', 'min:5', 'max:3600'],
            'timezone' => ['nullable', 'string', 'max:80'],
        ];
    }
}

