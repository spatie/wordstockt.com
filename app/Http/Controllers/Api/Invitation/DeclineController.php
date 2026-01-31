<?php

namespace App\Http\Controllers\Api\Invitation;

use App\Domain\Game\Actions\DeclineInvitationAction;
use App\Domain\User\Models\GameInvitation;
use Illuminate\Http\Request;

class DeclineController
{
    public function __invoke(
        Request $request,
        GameInvitation $invitation,
        DeclineInvitationAction $declineInvitationAction,
    ) {
        $declineInvitationAction->execute($invitation, $request->user());

        return response()->noContent();
    }
}
