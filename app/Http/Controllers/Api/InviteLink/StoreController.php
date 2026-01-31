<?php

namespace App\Http\Controllers\Api\InviteLink;

use App\Domain\Game\Actions\CreateGameInviteLinkAction;
use App\Domain\Game\Models\Game;
use App\Http\Resources\GameInviteLinkResource;
use Illuminate\Http\Request;

class StoreController
{
    public function __invoke(
        Request $request,
        Game $game,
        CreateGameInviteLinkAction $createInviteLinkAction,
    ): \App\Http\Resources\GameInviteLinkResource {
        $link = $createInviteLinkAction->execute($game, $request->user());

        return new GameInviteLinkResource($link->load(['game', 'inviter']));
    }
}
