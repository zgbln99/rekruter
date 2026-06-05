<?php

namespace App\Http\Requests\Candidates;

use App\Enums\CandidateSource;
use App\Enums\CandidateStatus;
use App\Enums\LicenseCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateCandidateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Progresywne wzbogacanie profilu — wszystkie pola opcjonalne (PATCH).
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'phone' => ['sometimes', 'string', 'max:32'],
            'first_name' => ['sometimes', 'string', 'max:120'],
            'last_name' => ['nullable', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:191'],
            'city' => ['nullable', 'string', 'max:120'],
            'country' => ['nullable', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:191'],
            'date_of_birth' => ['nullable', 'date'],
            'nationality' => ['nullable', 'string', 'max:120'],
            'availability_from' => ['nullable', 'date'],
            'experience_notes' => ['nullable', 'string'],
            'status' => ['sometimes', new Enum(CandidateStatus::class)],
            'license_categories' => ['sometimes', 'array'],
            'license_categories.*' => [Rule::in(LicenseCategory::values())],
            'has_adr' => ['sometimes', 'boolean'],
            'adr_expiry' => ['nullable', 'date'],
            'has_code_95' => ['sometimes', 'boolean'],
            'code_95_expiry' => ['nullable', 'date'],
            'driver_card_expiry' => ['nullable', 'date'],
            'has_hds' => ['sometimes', 'boolean'],
            'exp_reefer' => ['sometimes', 'boolean'],
            'exp_tilt' => ['sometimes', 'boolean'],
            'exp_international' => ['sometimes', 'boolean'],
            'lang_de' => ['sometimes', 'boolean'],
            'lang_en' => ['sometimes', 'boolean'],
            'work_history' => ['sometimes', 'array'],
            'work_history.*.employer' => ['nullable', 'string', 'max:191'],
            'work_history.*.position' => ['nullable', 'string', 'max:191'],
            'work_history.*.period' => ['nullable', 'string', 'max:120'],
            'work_history.*.description' => ['nullable', 'string', 'max:500'],
            'source' => ['nullable', Rule::in(CandidateSource::values())],
            'consent_rodo_at' => ['nullable', 'date'],
            'internal_notes' => ['nullable', 'string'],
        ];
    }
}
