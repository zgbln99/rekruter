<?php

namespace App\Http\Requests\Applications;

use Illuminate\Foundation\Http\FormRequest;

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
            'stage_id' => ['nullable', 'uuid', 'exists:pipeline_stages,id'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
