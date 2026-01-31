<?php

namespace App\Domain\Game\Actions;

use App\Domain\Game\Exceptions\GameException;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Notifications\GameInvitationNotification;
use App\Domain\User\Enums\InvitationStatus;
use App\Domain\User\Events\GameInvitationEvent;
use App\Domain\User\Models\GameInvitation;
use App\Domain\User\Models\User;

class InvitePlayerAction
{
    public function execute(Game $game, User $invitedUser): GameInvitation
    {
        $inviter = $game->players()->first();

        $this->validateInvitation($game, $inviter, $invitedUser);

        $invitation = $this->createInvitation($game, $inviter, $invitedUser);

        $this->notifyInvitee($invitation);

        return $invitation;
    }

    private function validateInvitation(Game $game, ?User $inviter, User $invitedUser): void
    {
        if ($inviter?->id === $invitedUser->id) {
            throw GameException::cannotInviteSelf();
        }

        if ($game->hasPlayer($invitedUser)) {
            throw GameException::userAlreadyInGame();
        }

        if ($this->hasPendingInvitation($game, $invitedUser)) {
            throw GameException::invitationAlreadyExists();
        }
    }

    private function hasPendingInvitation(Game $game, User $invitedUser): bool
    {
        return GameInvitation::query()
            ->where('game_id', $game->id)
            ->where('invitee_id', $invitedUser->id)
            ->pending()
            ->exists();
    }

    private function createInvitation(Game $game, User $inviter, User $invitedUser): GameInvitation
    {
        $existingInvitation = $this->findDeclinedInvitation($game, $invitedUser);

        if ($existingInvitation instanceof \App\Domain\User\Models\GameInvitation) {
            $existingInvitation->update(['status' => InvitationStatus::Pending]);

            return $existingInvitation;
        }

        return GameInvitation::create([
            'game_id' => $game->id,
            'inviter_id' => $inviter->id,
            'invitee_id' => $invitedUser->id,
            'status' => InvitationStatus::Pending,
        ]);
    }

    private function findDeclinedInvitation(Game $game, User $invitedUser): ?GameInvitation
    {
        return GameInvitation::query()
            ->where('game_id', $game->id)
            ->where('invitee_id', $invitedUser->id)
            ->where('status', InvitationStatus::Declined)
            ->first();
    }

    private function notifyInvitee(GameInvitation $invitation): void
    {
        $invitation->invitee->notify(new GameInvitationNotification($invitation->game, $invitation->inviter));

        broadcast(new GameInvitationEvent($invitation));
    }
}
