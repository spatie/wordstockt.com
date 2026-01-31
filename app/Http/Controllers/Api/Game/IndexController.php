<?php

namespace App\Http\Controllers\Api\Game;

use App\Domain\Game\Enums\GameStatus;
use App\Domain\Game\Models\Game;
use App\Http\Resources\GameListResource;
use Illuminate\Http\Request;

class IndexController
{
    public function __invoke(Request $request)
    {
        $user = $request->user();

        $activeGames = Game::forPlayer($user)
            ->whereIn('status', [GameStatus::Pending, GameStatus::Active])
            ->with(['players', 'gamePlayers', 'latestMove', 'pendingInvitation.invitee'])
            ->orderByDesc('updated_at')
            ->get();

        $finishedGames = Game::forPlayer($user)
            ->where('status', GameStatus::Finished)
            ->with(['players', 'gamePlayers', 'latestMove', 'pendingInvitation.invitee'])
            ->orderByDesc('updated_at')
            ->limit(100)
            ->get();

        $games = $activeGames->concat($finishedGames);

        return GameListResource::collection($games);
    }
}
