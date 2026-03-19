<?php

namespace App\Http\Requests\CvSecurity\Agent;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MemberSyncResultsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'results' => ['required', 'array', 'min:1', 'max:500'],
            'results.*.sync_item_id' => ['required', 'integer', 'min:1'],
            'results.*.status' => ['required', Rule::in(['done', 'failed'])],
            'results.*.retryable' => ['nullable', 'boolean'],
            'results.*.error' => ['nullable', 'string', 'max:2000'],
            'results.*.result' => ['nullable', 'array'],
        ];
    }
}

