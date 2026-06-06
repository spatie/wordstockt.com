<?php

namespace App\Domain\Game\Actions;

use App\Domain\Game\Exceptions\GameException;
use App\Domain\Game\Models\Game;
use App\Domain\User\Enums\InvitationStatus;
use App\Domain\User\Models\User;

class StartGameAction
{
    public function execute(Game $game, User $actor): Game
    {
        if (! $game->isPending()) {
            throw GameException::gameAlreadyStarted();
        }

        if (! $game->isCreator($actor)) {
            throw GameException::onlyCreatorCanStart();
        }

        if ($game->gamePlayers()->count() < 2) {
            throw GameException::notEnoughPlayersToStart();
        }

        $game->pendingInvitations()->update(['status' => InvitationStatus::Declined]);

        return app(ActivateGameAction::class)->execute($game->fresh());
    }
}
