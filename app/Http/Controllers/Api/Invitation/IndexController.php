<?php

namespace App\Http\Controllers\Api\Invitation;

use App\Domain\User\Models\GameInvitation;
use App\Http\Resources\GameInvitationResource;
use Illuminate\Http\Request;

class IndexController
{
    public function __invoke(Request $request)
    {
        $invitations = GameInvitation::forInvitee($request->user())
            ->pending()
            ->with(['game', 'inviter'])
            ->latest()
            ->get();

        return GameInvitationResource::collection($invitations);
    }
}
