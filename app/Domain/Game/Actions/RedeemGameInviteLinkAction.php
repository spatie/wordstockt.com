<?php

namespace App\Domain\Game\Actions;

use App\Domain\Game\Exceptions\GameException;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Notifications\GameInviteAcceptedNotification;
use App\Domain\User\Events\GameInvitationAcceptedEvent;
use App\Domain\User\Models\GameInviteLink;
use App\Domain\User\Models\User;

class RedeemGameInviteLinkAction
{
    public function __construct(
        private readonly JoinGameAction $joinGameAction,
    ) {}

    public function execute(GameInviteLink $link, User $user): Game
    {
        $this->validateLink($link, $user);

        $link->markAsUsed($user);

        $game = $this->joinGameAction->execute($link->game, $user);

        $this->notifyInviter($link, $game, $user);

        return $game;
    }

    private function validateLink(GameInviteLink $link, User $user): void
    {
        if ($link->isUsed()) {
            throw GameException::inviteLinkAlreadyUsed();
        }

        if (! $link->game->isPending()) {
            throw GameException::gameNotPending();
        }

        if ($link->game->hasPlayer($user)) {
            throw GameException::userAlreadyInGame();
        }

        if ($link->inviter_id === $user->id) {
            throw GameException::cannotPlayAgainstSelf();
        }
    }

    private function notifyInviter(GameInviteLink $link, Game $game, User $joiner): void
    {
        $link->inviter->notify(new GameInviteAcceptedNotification($game, $joiner));

        broadcast(new GameInvitationAcceptedEvent($game, $link->inviter, $joiner));
    }
}
