<?php

namespace App\Domain\Game\Actions;

use App\Domain\Game\Exceptions\GameException;
use App\Domain\Game\Models\Game;
use App\Domain\User\Models\GameInvitation;
use App\Domain\User\Models\User;

class DeleteGameAction
{
    public function execute(Game $game, User $user): void
    {
        $this->ensureUserIsCreator($game, $user);
        $this->ensureGameCanBeDeleted($game);

        GameInvitation::where('game_id', $game->id)->delete();
        $game->gamePlayers()->delete();
        $game->moves()->delete();

        $game->delete();
    }

    private function ensureUserIsCreator(Game $game, User $user): void
    {
        if (! $game->isCreator($user)) {
            throw GameException::notGameCreator();
        }
    }

    private function ensureGameCanBeDeleted(Game $game): void
    {
        if (! $game->isPending()) {
            throw GameException::cannotDeleteActiveGame();
        }
    }
}
