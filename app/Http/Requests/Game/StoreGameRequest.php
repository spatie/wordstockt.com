<?php

namespace App\Http\Requests\Game;

use Illuminate\Foundation\Http\FormRequest;

class StoreGameRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'language' => ['sometimes', 'string', 'in:nl,en'],
            'opponent_username' => ['sometimes', 'string', 'exists:users,username'],
            'board_type' => ['sometimes', 'string', 'in:standard,no_bonuses,custom'],
            'board_template' => ['required_if:board_type,custom', 'nullable', 'array'],
            'board_template.*' => ['array'],
            'board_template.*.*' => ['nullable', 'string', 'in:3W,2W,3L,2L,STAR'],
            'is_public' => ['sometimes', 'boolean'],
        ];
    }
}
