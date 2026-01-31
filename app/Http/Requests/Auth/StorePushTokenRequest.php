<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class StorePushTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'regex:/^ExponentPushToken\[.+\]$/'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ];
    }

    #[\Override]
    public function messages(): array
    {
        return [
            'token.regex' => 'The token must be a valid Expo push token.',
        ];
    }
}
