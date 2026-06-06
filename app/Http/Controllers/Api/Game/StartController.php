<?php

namespace App\Http\Controllers\Api\Game;

use App\Domain\Game\Actions\StartGameAction;
use App\Domain\Game\Models\Game;
use App\Http\Resources\GameResource;
use Illuminate\Http\Request;

class StartController
{
    public function __invoke(Request $request, Game $game): GameResource
    {
        $game = app(StartGameAction::class)->execute($game, $request->user());

        return new GameResource($game);
    }
}
