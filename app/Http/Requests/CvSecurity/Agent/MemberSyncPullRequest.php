<?php

namespace App\Http\Requests\CvSecurity\Agent;

use Illuminate\Foundation\Http\FormRequest;

class MemberSyncPullRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'limit' => ['nullable', 'integer', 'min:1', 'max:500'],
        ];
    }
}

