<?php

namespace App\Http\Requests\Documents;

use App\Enums\DocumentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDocumentRequest extends FormRequest
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
            'type' => ['required', Rule::in(DocumentType::values())],
            // Maks. 25 MB; obrazy i PDF.
            'file' => ['required', 'file', 'max:25600', 'mimes:jpg,jpeg,png,webp,heic,pdf'],
        ];
    }
}
