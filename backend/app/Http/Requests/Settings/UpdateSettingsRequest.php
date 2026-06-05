<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
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
            'agency_name' => ['required', 'string', 'max:120'],
            'agency_phone' => ['nullable', 'string', 'max:40'],
            'agency_email' => ['nullable', 'email', 'max:191'],
            'agency_website' => ['nullable', 'string', 'max:191'],
        ];
    }
}
