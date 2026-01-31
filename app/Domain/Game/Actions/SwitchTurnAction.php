<?php

namespace App\Domain\Game\Actions;

use App\Domain\Game\Models\Game;

class SwitchTurnAction
{
    public function execute(Game $game): void
    {
        $players = $game->gamePlayers()->orderBy('turn_order')->get();
        $currentIndex = $players->search(fn ($p): bool => $p->user_id === $game->current_turn_user_id);
        $nextIndex = ($currentIndex + 1) % $players->count();

        $game->update([
            'current_turn_user_id' => $players[$nextIndex]->user_id,
            'turn_expires_at' => now()->addHours(Game::turnTimeoutHours()),
            'last_turn_reminder_sent' => null,
        ]);
    }
}
