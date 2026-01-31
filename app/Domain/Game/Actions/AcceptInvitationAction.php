<?php

namespace App\Domain\Game\Actions;

use App\Domain\Game\Exceptions\GameException;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Notifications\GameInviteAcceptedNotification;
use App\Domain\User\Events\GameInvitationAcceptedEvent;
use App\Domain\User\Models\GameInvitation;
use App\Domain\User\Models\User;

class AcceptInvitationAction
{
    public function __construct(
        private readonly JoinGameAction $joinGameAction,
    ) {}

    public function execute(GameInvitation $invitation, User $user): Game
    {
        $this->validateInvitation($invitation, $user);

        $invitation->accept();

        $game = $this->joinGameAction->execute($invitation->game, $user);

        $this->notifyInviter($invitation, $game, $user);

        return $game;
    }

    private function validateInvitation(GameInvitation $invitation, User $user): void
    {
        if ($invitation->invitee_id !== $user->id) {
            throw GameException::notInvitee();
        }

        if (! $invitation->isPending()) {
            throw GameException::invitationNotPending();
        }

        if ($invitation->game->hasPlayer($user)) {
            throw GameException::userAlreadyInGame();
        }
    }

    private function notifyInviter(GameInvitation $invitation, Game $game, User $accepter): void
    {
        $invitation->inviter->notify(new GameInviteAcceptedNotification($game, $accepter));

        broadcast(new GameInvitationAcceptedEvent($game, $invitation->inviter, $accepter));
    }
}
