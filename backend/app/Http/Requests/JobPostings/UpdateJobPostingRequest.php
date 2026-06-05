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
            'driver_type' => ['nullable', 'string', 'max:120'],
            'trailer_type' => ['nullable', 'string', 'max:120'],
            'vehicle_type' => ['nullable', 'string', 'max:191'],
            'cargo' => ['nullable', 'string', 'max:191'],
            'routes_info' => ['nullable', 'string'],
            'accommodation' => ['nullable', 'string'],
            'onsite_contact' => ['nullable', 'string'],
            'arrival_info' => ['nullable', 'string', 'max:191'],
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
            'call_script' => ['sometimes', 'array'],
            'call_script.*' => ['string', 'max:255'],
            'required_categories' => ['sometimes', 'array'],
            'required_categories.*' => [Rule::in(LicenseCategory::values())],
            'requirements' => ['sometimes', 'array'],
            'requirements.*' => ['boolean'],
            'location' => ['nullable', 'string', 'max:191'],
            'salary_range' => ['nullable', 'string', 'max:120'],
            'status' => ['sometimes', new Enum(JobPostingStatus::class)],
        ];
    }
}
