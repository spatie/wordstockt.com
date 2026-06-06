<?php

namespace App\Http\Controllers\Api\Game;

use App\Domain\Game\Enums\GameStatus;
use App\Domain\Game\Models\Game;
use App\Http\Resources\PublicGameResource;
use App\Support\AppVersion;
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
            // Hide 3-4 player games from clients that can't render them.
            ->when(
                ! AppVersion::supportsMultiplayer($request),
                fn ($query) => $query->where('max_players', '<=', 2)
            )
            ->with(['players', 'gamePlayers'])
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        return PublicGameResource::collection($games);
    }
}
