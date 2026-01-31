<?php

namespace App\Http\Controllers\Api\Game;

use App\Domain\Game\Actions\InvitePlayerAction;
use App\Domain\Game\Models\Game;
use App\Domain\User\Models\User;
use App\Http\Requests\Game\InviteRequest;
use App\Http\Resources\GameInvitationResource;

class InviteController
{
    public function __invoke(InviteRequest $request, Game $game): \App\Http\Resources\GameInvitationResource
    {
        $invitedUser = User::where('ulid', $request->validated('user_ulid'))->firstOrFail();

        $invitation = app(InvitePlayerAction::class)->execute($game, $invitedUser);

        return new GameInvitationResource($invitation->load(['game', 'inviter', 'invitee']));
    }
}
