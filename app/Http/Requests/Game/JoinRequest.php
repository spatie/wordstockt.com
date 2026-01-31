<?php

namespace App\Http\Requests\Game;

use Illuminate\Foundation\Http\FormRequest;

class JoinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('join', $this->route('game'));
    }

    public function rules(): array
    {
        return [];
    }
}
