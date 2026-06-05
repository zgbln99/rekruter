<?php

namespace App\Http\Requests\Tasks;

use App\Enums\TaskStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateTaskRequest extends FormRequest
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
            'status' => ['sometimes', new Enum(TaskStatus::class)],
            'title' => ['sometimes', 'string', 'max:191'],
            'description' => ['nullable', 'string'],
            'due_at' => ['nullable', 'date'],
        ];
    }
}
