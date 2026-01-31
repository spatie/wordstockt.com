<?php

namespace App\Http\Controllers\Api\Game;

use App\Domain\Game\Enums\GameStatus;
use App\Domain\Game\Models\Game;
use App\Http\Resources\PendingGameResource;
use Illuminate\Http\Request;

class PendingController
{
    public function __invoke(Request $request)
    {
        $user = $request->user();

        $games = Game::where('status', GameStatus::Pending)
            ->whereDoesntHave('players', fn ($query) => $query->where('users.id', $user->id))
            ->with(['players'])
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return PendingGameResource::collection($games);
    }
}
