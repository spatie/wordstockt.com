<?php

namespace App\Http\Controllers\Api\Game;

use App\Domain\Game\Actions\JoinGameAction;
use App\Domain\Game\Models\Game;
use App\Http\Requests\Game\JoinRequest;
use App\Http\Resources\GameResource;

class JoinController
{
    public function __invoke(JoinRequest $request, Game $game): \App\Http\Resources\GameResource
    {
        $game = app(JoinGameAction::class)->execute($game, $request->user());

        return new GameResource($game);
    }
}
