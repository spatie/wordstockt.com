<?php

namespace App\Http\Requests\Game;

use Illuminate\Foundation\Http\FormRequest;

class WordInfoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('view', $this->route('game'));
    }

    public function rules(): array
    {
        return [
            'x' => ['required', 'integer', 'min:0', 'max:14'],
            'y' => ['required', 'integer', 'min:0', 'max:14'],
        ];
    }
}
