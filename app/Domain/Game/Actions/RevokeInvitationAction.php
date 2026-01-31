<?php

namespace App\Domain\Game\Actions;

use App\Domain\Game\Exceptions\GameException;
use App\Domain\User\Enums\InvitationStatus;
use App\Domain\User\Models\GameInvitation;
use App\Domain\User\Models\User;

class RevokeInvitationAction
{
    public function execute(GameInvitation $invitation, User $user): void
    {
        $this->validateRevocation($invitation, $user);

        $invitation->delete();
    }

    private function validateRevocation(GameInvitation $invitation, User $user): void
    {
        if ($invitation->inviter_id !== $user->id) {
            throw GameException::notAuthorized();
        }

        if ($invitation->status !== InvitationStatus::Pending) {
            throw GameException::invitationNotPending();
        }
    }
}
