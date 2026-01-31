<?php

namespace App\Http\Controllers\Api\Invitation;

use App\Domain\Game\Actions\RevokeInvitationAction;
use App\Domain\User\Models\GameInvitation;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class RevokeController
{
    public function __invoke(
        Request $request,
        GameInvitation $invitation,
        RevokeInvitationAction $action,
    ): Response {
        $action->execute($invitation, $request->user());

        return response()->noContent();
    }
}
