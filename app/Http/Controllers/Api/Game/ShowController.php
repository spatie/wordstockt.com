<?php

namespace App\Http\Controllers\Api\Game;

use App\Domain\Game\Models\Game;
use App\Http\Requests\Game\ShowRequest;
use App\Http\Resources\GameResource;

class ShowController
{
    public function __invoke(ShowRequest $request, Game $game): \App\Http\Resources\GameResource
    {
        return new GameResource(
            $game->load(['gamePlayers.user', 'currentTurnUser', 'winner', 'latestMove.user', 'pendingInvitation.invitee'])
        );
    }
}
