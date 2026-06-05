<?php

namespace App\Http\Requests\Candidates;

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
            'status' => ['sometimes', new Enum(CandidateStatus::class)],
            'license_categories' => ['sometimes', 'array'],
            'license_categories.*' => [Rule::in(LicenseCategory::values())],
            'has_adr' => ['sometimes', 'boolean'],
            'adr_expiry' => ['nullable', 'date'],
            'has_code_95' => ['sometimes', 'boolean'],
            'code_95_expiry' => ['nullable', 'date'],
            'driver_card_expiry' => ['nullable', 'date'],
            'source' => ['nullable', 'string', 'max:60'],
            'consent_rodo_at' => ['nullable', 'date'],
            'internal_notes' => ['nullable', 'string'],
        ];
    }
}
