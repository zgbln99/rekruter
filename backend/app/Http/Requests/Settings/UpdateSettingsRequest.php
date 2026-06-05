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
            'openai_api_key' => ['nullable', 'string', 'max:255'],
            'openai_model' => ['nullable', 'string', 'max:60'],
            'placement_fee' => ['nullable', 'numeric', 'min:0'],
            'placement_currency' => ['nullable', 'string', 'size:3'],
            'message_templates' => ['nullable', 'array'],
            'message_templates.*.name' => ['nullable', 'string', 'max:80'],
            'message_templates.*.body' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
