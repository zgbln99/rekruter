<?php

namespace App\Http\Requests\Profiles;

use Illuminate\Foundation\Http\FormRequest;

class SendProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'recipient_email' => ['required', 'email'],
            'company_id' => ['nullable', 'uuid'],
            'job_posting_id' => ['nullable', 'uuid'],
        ];
    }
}
