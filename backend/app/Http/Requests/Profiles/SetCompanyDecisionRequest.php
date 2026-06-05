<?php

namespace App\Http\Requests\Profiles;

use App\Enums\CompanyDecision;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class SetCompanyDecisionRequest extends FormRequest
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
            'decision' => ['required', new Enum(CompanyDecision::class)],
        ];
    }
}
