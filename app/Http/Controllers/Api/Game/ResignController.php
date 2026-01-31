<?php

namespace App\Http\Controllers\Api\Game;

use App\Domain\Game\Actions\ResignAction;
use App\Domain\Game\Models\Game;
use App\Http\Requests\Game\ResignRequest;
use App\Http\Resources\GameResource;
use App\Http\Resources\MoveResource;

class ResignController
{
    public function __invoke(ResignRequest $request, Game $game)
    {
        $move = app(ResignAction::class)->execute($game, $request->user());

        return new GameResource($game->fresh())
            ->additional(['move' => new MoveResource($move)->resolve($request)]);
    }
}
