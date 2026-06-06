<?php

namespace App\Domain\Game\Actions;

use App\Domain\Game\Enums\GameStatus;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Notifications\YourTurnNotification;
use App\Domain\User\Models\User;

class ActivateGameAction
{
    public function execute(Game $game): Game
    {
        $firstPlayer = $game->gamePlayers()->active()->get()->random();

        $game->update([
            'status' => GameStatus::Active,
            'current_turn_user_id' => $firstPlayer->user_id,
            'turn_expires_at' => now()->addHours(Game::turnTimeoutHours()),
            'last_turn_reminder_sent' => null,
        ]);

        $freshGame = $game->fresh(['players', 'gamePlayers']);

        $firstPlayerUser = User::find($freshGame->current_turn_user_id);
        $firstPlayerUser->notify(new YourTurnNotification($freshGame));

        return $freshGame;
    }
}
