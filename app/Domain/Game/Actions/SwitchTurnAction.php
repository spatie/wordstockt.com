<?php

namespace App\Domain\Game\Actions;

use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\GamePlayer;

class SwitchTurnAction
{
    public function execute(Game $game): void
    {
        $players = $game->gamePlayers()->orderBy('turn_order')->get();
        $currentIndex = $players->search(fn (GamePlayer $p): bool => $p->user_id === $game->current_turn_user_id);

        $count = $players->count();
        $nextPlayer = null;

        for ($step = 1; $step <= $count; $step++) {
            $candidate = $players[($currentIndex + $step) % $count];

            if (! $candidate->hasLeft()) {
                $nextPlayer = $candidate;
                break;
            }
        }

        if (! $nextPlayer instanceof GamePlayer) {
            return;
        }

        $game->update([
            'current_turn_user_id' => $nextPlayer->user_id,
            'turn_expires_at' => now()->addHours(Game::turnTimeoutHours()),
            'last_turn_reminder_sent' => null,
        ]);
    }
}
