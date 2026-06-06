<?php

namespace App\Http\Requests\Tasks;

use App\Enums\TaskType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreTaskRequest extends FormRequest
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
            'candidate_id' => ['required', 'uuid', 'exists:candidates,id'],
            'title' => ['required', 'string', 'max:191'],
            'description' => ['nullable', 'string'],
            'due_at' => ['nullable', 'date'],
            'type' => ['nullable', new Enum(TaskType::class)],
            'assigned_to' => ['nullable', 'uuid', 'exists:users,id'],
        ];
    }
}
