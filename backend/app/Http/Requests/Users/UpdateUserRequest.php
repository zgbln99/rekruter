<?php

namespace App\Http\Requests\Users;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateUserRequest extends FormRequest
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
        $tenantId = $this->user()->tenant_id;
        $targetId = $this->route('user')->id ?? null;

        return [
            'name' => ['sometimes', 'string', 'max:120'],
            'email' => [
                'sometimes', 'email', 'max:191',
                Rule::unique('users', 'email')->where('tenant_id', $tenantId)->ignore($targetId),
            ],
            'password' => ['nullable', 'string', 'min:8'],
            'role' => ['sometimes', new Enum(UserRole::class)],
            'phone' => ['nullable', 'string', 'max:32'],
        ];
    }
}
