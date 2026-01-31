<?php

namespace App\Http\Requests\Game;

use Illuminate\Foundation\Http\FormRequest;

class InviteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('invite', $this->route('game'));
    }

    public function rules(): array
    {
        return [
            'user_ulid' => ['required', 'string', 'size:26', 'exists:users,ulid'],
        ];
    }
}
