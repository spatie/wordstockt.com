<?php

namespace App\Http\Controllers\Api\InviteLink;

use App\Domain\Game\Exceptions\GameException;
use App\Domain\User\Models\GameInviteLink;
use App\Http\Resources\GameInviteLinkResource;

class ShowController
{
    public function __invoke(string $code): \App\Http\Resources\GameInviteLinkResource
    {
        $link = GameInviteLink::where('code', $code)->first();

        if (! $link) {
            throw GameException::inviteLinkNotFound();
        }

        return new GameInviteLinkResource($link->load(['game', 'inviter']));
    }
}
