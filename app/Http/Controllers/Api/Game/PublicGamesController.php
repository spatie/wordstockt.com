<?php

namespace App\Http\Controllers\Api\Game;

use App\Domain\Game\Enums\GameStatus;
use App\Domain\Game\Models\Game;
use App\Http\Resources\PublicGameResource;
use Illuminate\Http\Request;

class PublicGamesController
{
    public function __invoke(Request $request)
    {
        $user = $request->user();

        $games = Game::query()
            ->where('status', GameStatus::Pending)
            ->where('is_public', true)
            ->whereDoesntHave('players', fn ($query) => $query->where('users.id', $user->id))
            ->with(['players', 'gamePlayers'])
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        return PublicGameResource::collection($games);
    }
}
