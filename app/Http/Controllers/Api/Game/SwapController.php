<?php

namespace App\Http\Controllers\Api\Game;

use App\Domain\Game\Actions\SwapTilesAction;
use App\Domain\Game\Models\Game;
use App\Http\Requests\Game\SwapRequest;
use App\Http\Resources\GameResource;
use App\Http\Resources\MoveResource;

class SwapController
{
    public function __invoke(SwapRequest $request, Game $game)
    {
        $move = app(SwapTilesAction::class)->execute($game, $request->user(), $request->validated('tiles'));

        return new GameResource($game->fresh())
            ->additional(['move' => new MoveResource($move)->resolve($request)]);
    }
}
