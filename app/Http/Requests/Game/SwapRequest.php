<?php

namespace App\Http\Requests\Game;

use Illuminate\Foundation\Http\FormRequest;

class SwapRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('play', $this->route('game'));
    }

    public function rules(): array
    {
        return [
            'tiles' => ['required', 'array', 'min:1', 'max:7'],
            'tiles.*.letter' => ['required', 'string', 'max:2'],
            'tiles.*.points' => ['required', 'integer', 'min:0'],
        ];
    }
}
