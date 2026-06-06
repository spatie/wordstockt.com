<?php

namespace App\Http\Requests\Game;

use App\Support\AppVersion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

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
            'max_players' => ['sometimes', 'integer', 'between:2,4'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ((int) $this->input('max_players', 2) <= 2) {
                return;
            }

            if (AppVersion::supportsMultiplayer($this)) {
                return;
            }

            $validator->errors()->add(
                'max_players',
                'Please update the app to create games with more than two players.'
            );
        });
    }
}
