<?php

namespace App\Http\Requests\JobPostings;

use App\Enums\JobPostingStatus;
use App\Enums\LicenseCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreJobPostingRequest extends FormRequest
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
            'company_id' => ['required', 'uuid', 'exists:companies,id'],
            'title' => ['required', 'string', 'max:191'],
            'description' => ['nullable', 'string'],
            'required_categories' => ['nullable', 'array'],
            'required_categories.*' => [Rule::in(LicenseCategory::values())],
            'location' => ['nullable', 'string', 'max:191'],
            'salary_range' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', new Enum(JobPostingStatus::class)],
        ];
    }
}
