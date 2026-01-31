<?php

namespace App\Http\Requests\Auth\Concerns;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

trait HasRegistrationRules
{
    protected function usernameRules(?int $ignoreUserId = null): array
    {
        $unique = $ignoreUserId
            ? Rule::unique('users')->ignore($ignoreUserId)
            : 'unique:users';

        return [
            'required',
            'string',
            'min:3',
            'max:20',
            $unique,
            'regex:/^[a-zA-Z0-9_]+$/',
        ];
    }

    protected function emailRules(): array
    {
        return ['required', 'email', 'unique:users'];
    }

    protected function passwordRules(): array
    {
        return ['required', 'confirmed', Password::min(8)];
    }
}
