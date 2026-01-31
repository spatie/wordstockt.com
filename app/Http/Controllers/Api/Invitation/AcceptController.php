<?php

namespace App\Http\Controllers\Api\Invitation;

use App\Domain\Game\Actions\AcceptInvitationAction;
use App\Domain\User\Models\GameInvitation;
use App\Http\Resources\GameResource;
use Illuminate\Http\Request;

class AcceptController
{
    public function __invoke(
        Request $request,
        GameInvitation $invitation,
        AcceptInvitationAction $acceptInvitationAction,
    ): \App\Http\Resources\GameResource {
        $game = $acceptInvitationAction->execute($invitation, $request->user());

        return new GameResource($game);
    }
}
