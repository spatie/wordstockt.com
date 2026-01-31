<?php

use App\Domain\Game\Models\Game;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('game.{gameUlid}', function ($user, $gameUlid) {
    $game = Game::where('ulid', $gameUlid)->first();

    if (! $game) {
        return false;
    }

    return $game->hasPlayer($user);
});

Broadcast::channel('user.{userUlid}', fn ($user, $userUlid): bool => $user->ulid === $userUlid);
