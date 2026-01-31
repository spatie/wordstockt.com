<?php

namespace App\Http\Controllers\Api\Game;

use App\Domain\Game\Actions\PlayMoveAction;
use App\Domain\Game\Models\Game;
use App\Http\Requests\Game\MoveRequest;
use App\Http\Resources\AchievementResource;
use App\Http\Resources\GameResource;
use App\Http\Resources\MoveResource;

class MoveController
{
    public function __invoke(MoveRequest $request, Game $game)
    {
        $move = app(PlayMoveAction::class)->execute($game, $request->user(), $request->validated('tiles'));

        $achievements = $move->getRelation('unlockedAchievements') ?? collect();

        return new GameResource($game->fresh())
            ->additional([
                'move' => new MoveResource($move)->resolve($request),
                'achievements' => AchievementResource::collection($achievements)->resolve($request),
            ]);
    }
}
