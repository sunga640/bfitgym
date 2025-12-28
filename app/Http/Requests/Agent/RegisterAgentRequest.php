<?php

namespace App\Http\Requests\Agent;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterAgentRequest extends FormRequest
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
            'enrollment_code' => ['required', 'string', 'max:64'],
            'name' => ['required', 'string', 'max:150'],
            'os' => ['required', Rule::in(['windows', 'macos', 'linux'])],
            'app_version' => ['nullable', 'string', 'max:50'],
        ];
    }
}
