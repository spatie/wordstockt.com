<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\Auth\Concerns\HasRegistrationRules;
use Illuminate\Foundation\Http\FormRequest;

class ConvertGuestRequest extends FormRequest
{
    use HasRegistrationRules;

    public function authorize(): bool
    {
        return $this->user()?->isGuest() ?? false;
    }

    public function rules(): array
    {
        return [
            'username' => $this->usernameRules($this->user()->id),
            'email' => $this->emailRules(),
            'password' => $this->passwordRules(),
        ];
    }
}
