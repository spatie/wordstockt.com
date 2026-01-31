<?php

namespace App\Domain\Game\Actions;

use App\Domain\Game\Exceptions\GameException;
use App\Domain\Game\Models\Game;
use App\Domain\User\Models\GameInviteLink;
use App\Domain\User\Models\User;

class CreateGameInviteLinkAction
{
    public function execute(Game $game, User $inviter): GameInviteLink
    {
        if (! $game->hasPlayer($inviter)) {
            throw GameException::notAPlayer();
        }

        if (! $game->isPending()) {
            throw GameException::gameNotPending();
        }

        return GameInviteLink::create([
            'game_id' => $game->id,
            'inviter_id' => $inviter->id,
        ]);
    }
}
