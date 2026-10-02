<?php

namespace App\Http\Requests\Game;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MoveReactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $game = $this->route('game');
        $move = $this->route('move');

        return $this->user()->can('play', $game)
            && $move->game_id === $game->id
            && $move->user_id !== $this->user()->id;
    }

    public function rules(): array
    {
        return [
            'reaction' => ['required', Rule::in(['clap', 'wow', 'laugh', 'flex'])],
        ];
    }
}
