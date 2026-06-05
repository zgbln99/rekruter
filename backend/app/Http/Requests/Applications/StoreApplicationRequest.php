<?php

namespace App\Http\Requests\Applications;

use App\Enums\ApplicationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreApplicationRequest extends FormRequest
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
            'candidate_id' => ['required', 'uuid', 'exists:candidates,id'],
            'job_posting_id' => ['required', 'uuid', 'exists:job_postings,id'],
            'status' => ['nullable', new Enum(ApplicationStatus::class)],
            'notes' => ['nullable', 'string'],
        ];
    }
}
