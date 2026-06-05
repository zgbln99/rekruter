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
            'driver_type' => ['nullable', 'string', 'max:120'],
            'trailer_type' => ['nullable', 'string', 'max:120'],
            'country' => ['nullable', 'string', 'max:120'],
            'region_base' => ['nullable', 'string', 'max:120'],
            'work_system' => ['nullable', 'string', 'max:60'],
            'salary_amount' => ['nullable', 'string', 'max:120'],
            'currency' => ['nullable', 'string', 'max:10'],
            'start_date' => ['nullable', 'date'],
            'required_language' => ['nullable', 'string', 'max:120'],
            'required_experience' => ['nullable', 'string', 'max:191'],
            'description' => ['nullable', 'string'],
            'public_description' => ['nullable', 'string'],
            'recruiter_notes' => ['nullable', 'string'],
            'call_script' => ['nullable', 'array'],
            'call_script.*' => ['string', 'max:255'],
            'required_categories' => ['nullable', 'array'],
            'required_categories.*' => [Rule::in(LicenseCategory::values())],
            'requirements' => ['nullable', 'array'],
            'requirements.*' => ['boolean'],
            'location' => ['nullable', 'string', 'max:191'],
            'salary_range' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', new Enum(JobPostingStatus::class)],
        ];
    }
}
