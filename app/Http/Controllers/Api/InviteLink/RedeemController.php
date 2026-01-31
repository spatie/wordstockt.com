<?php

namespace App\Http\Controllers\Api\InviteLink;

use App\Domain\Game\Actions\RedeemGameInviteLinkAction;
use App\Domain\Game\Exceptions\GameException;
use App\Domain\User\Models\GameInviteLink;
use App\Http\Resources\GameResource;
use Illuminate\Http\Request;

class RedeemController
{
    public function __invoke(
        Request $request,
        string $code,
        RedeemGameInviteLinkAction $redeemInviteLinkAction,
    ): \App\Http\Resources\GameResource {
        $link = GameInviteLink::where('code', $code)->first();

        if (! $link) {
            throw GameException::inviteLinkNotFound();
        }

        $game = $redeemInviteLinkAction->execute($link, $request->user());

        return new GameResource($game);
    }
}
