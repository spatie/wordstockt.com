<?php

namespace App\Http\Requests\Game;

use Illuminate\Foundation\Http\FormRequest;

class PassRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('play', $this->route('game'));
    }

    public function rules(): array
    {
        return [];
    }
}
