<?php

namespace App\Http\Requests\Agent;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AgentCommandResultRequest extends FormRequest
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
            'status' => ['required', Rule::in(['done', 'failed'])],
            'result' => ['nullable', 'array'],
            // Preferred key (v1+)
            'error_message' => ['nullable', 'string', 'max:5000'],
            // Backwards compatible alias (older agents)
            'error' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
