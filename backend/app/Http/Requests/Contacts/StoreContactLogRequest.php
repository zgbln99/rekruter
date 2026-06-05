<?php

namespace App\Http\Requests\Contacts;

use App\Enums\ContactChannel;
use App\Enums\ContactOutcome;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreContactLogRequest extends FormRequest
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
            'channel' => ['required', new Enum(ContactChannel::class)],
            'outcome' => ['required', new Enum(ContactOutcome::class)],
            'note' => ['nullable', 'string'],
            'contacted_at' => ['nullable', 'date'],
            'next_contact_at' => ['nullable', 'date'],
        ];
    }
}
