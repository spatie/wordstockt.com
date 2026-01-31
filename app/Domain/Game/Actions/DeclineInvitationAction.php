<?php

namespace App\Domain\Game\Actions;

use App\Domain\Game\Exceptions\GameException;
use App\Domain\User\Events\GameInvitationDeclinedEvent;
use App\Domain\User\Models\GameInvitation;
use App\Domain\User\Models\User;

class DeclineInvitationAction
{
    public function execute(GameInvitation $invitation, User $user): void
    {
        $this->validateInvitation($invitation, $user);

        $invitation->decline();

        broadcast(new GameInvitationDeclinedEvent($invitation));
    }

    private function validateInvitation(GameInvitation $invitation, User $user): void
    {
        if ($invitation->invitee_id !== $user->id) {
            throw GameException::notInvitee();
        }

        if (! $invitation->isPending()) {
            throw GameException::invitationNotPending();
        }
    }
}
