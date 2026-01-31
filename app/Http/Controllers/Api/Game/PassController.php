<?php

namespace App\Http\Controllers\Api\Game;

use App\Domain\Game\Actions\PassAction;
use App\Domain\Game\Models\Game;
use App\Http\Requests\Game\PassRequest;
use App\Http\Resources\GameResource;
use App\Http\Resources\MoveResource;

class PassController
{
    public function __invoke(PassRequest $request, Game $game)
    {
        $move = app(PassAction::class)->execute($game, $request->user());

        return new GameResource($game->fresh())
            ->additional(['move' => new MoveResource($move)->resolve($request)]);
    }
}
