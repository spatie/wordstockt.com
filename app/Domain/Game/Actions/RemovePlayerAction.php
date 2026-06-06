<?php

namespace App\Domain\Game\Actions;

use App\Domain\Game\Models\Game;
use App\Domain\User\Models\User;

class RemovePlayerAction
{
    public function execute(Game $game, User $user, string $reason): void
    {
        $gamePlayer = $game->getGamePlayer($user);

        if (! $gamePlayer || $gamePlayer->hasLeft()) {
            return;
        }

        $gamePlayer->update([
            'left_at' => now(),
            'left_reason' => $reason,
            'rack_tiles' => [],
        ]);

        if ($game->fresh()->gamePlayers()->active()->count() <= 1) {
            app(EndGameAction::class)->execute($game->fresh());

            return;
        }

        app(SwitchTurnAction::class)->execute($game->fresh());
    }
}
