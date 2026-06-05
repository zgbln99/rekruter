<?php

namespace App\Http\Requests\JobPostings;

use App\Enums\JobPostingStatus;
use App\Enums\LicenseCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateJobPostingRequest extends FormRequest
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
            'title' => ['sometimes', 'string', 'max:191'],
            'description' => ['nullable', 'string'],
            'required_categories' => ['sometimes', 'array'],
            'required_categories.*' => [Rule::in(LicenseCategory::values())],
            'location' => ['nullable', 'string', 'max:191'],
            'salary_range' => ['nullable', 'string', 'max:120'],
            'status' => ['sometimes', new Enum(JobPostingStatus::class)],
        ];
    }
}
