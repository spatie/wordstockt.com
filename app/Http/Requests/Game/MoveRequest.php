<?php

namespace App\Http\Requests\Game;

use App\Http\Requests\Concerns\ValidatesTileArray;
use Illuminate\Foundation\Http\FormRequest;

class MoveRequest extends FormRequest
{
    use ValidatesTileArray;

    public function authorize(): bool
    {
        return $this->user()->can('play', $this->route('game'));
    }

    public function rules(): array
    {
        return $this->tileRules();
    }
}
