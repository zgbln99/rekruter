<?php

namespace App\Http\Requests\Candidates;

use App\Enums\CandidateStatus;
use App\Enums\ContactChannel;
use App\Enums\ContactOutcome;
use App\Enums\LicenseCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreCandidateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Minimalny zestaw pól pod KPI 60s — wymagane tylko telefon i imię.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'max:32'],
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['nullable', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:191'],
            'city' => ['nullable', 'string', 'max:120'],
            'country' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', new Enum(CandidateStatus::class)],
            'license_categories' => ['nullable', 'array'],
            'license_categories.*' => [Rule::in(LicenseCategory::values())],
            'has_adr' => ['nullable', 'boolean'],
            'has_code_95' => ['nullable', 'boolean'],
            'source' => ['nullable', 'string', 'max:60'],
            'internal_notes' => ['nullable', 'string'],

            // Opcjonalny pierwszy kontakt logowany razem z dodaniem kandydata.
            'contact' => ['nullable', 'array'],
            'contact.channel' => ['required_with:contact', new Enum(ContactChannel::class)],
            'contact.outcome' => ['required_with:contact', new Enum(ContactOutcome::class)],
            'contact.note' => ['nullable', 'string'],
            'contact.contacted_at' => ['nullable', 'date'],
            'contact.next_contact_at' => ['nullable', 'date'],
        ];
    }
}
