<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            'username' => ['sometimes', 'string', 'min:3', 'max:20', 'unique:users,username,'.$userId, 'regex:/^[a-zA-Z0-9_]+$/'],
            'email' => ['sometimes', 'email', 'unique:users,email,'.$userId],
            'avatar' => ['sometimes', 'nullable', 'string', 'max:255'],
            'avatar_color' => ['sometimes', 'nullable', 'string', 'max:7', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ];
    }
}
