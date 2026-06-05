<?php

namespace App\Http\Requests\JobPostings;

use App\Enums\CandidateSource;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateCandidateFromOfferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Krok 1 szybkiego tworzenia — tylko imię, nazwisko, telefon (KPI < 60s).
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['nullable', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:32'],
            'source' => ['nullable', Rule::in(CandidateSource::values())],
        ];
    }
}
