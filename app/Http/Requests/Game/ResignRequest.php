<?php

namespace App\Http\Requests\Game;

use Illuminate\Foundation\Http\FormRequest;

class ResignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('resign', $this->route('game'));
    }

    public function rules(): array
    {
        return [];
    }
}
