<?php

namespace App\Http\Controllers\Api\Game;

use App\Domain\Game\Actions\CreateGameAction;
use App\Http\Requests\Game\StoreGameRequest;
use App\Http\Resources\GameResource;
use Symfony\Component\HttpFoundation\Response;

class StoreController
{
    public function __invoke(StoreGameRequest $request): Response
    {
        $game = app(CreateGameAction::class)->execute(
            $request->user(),
            $request->validated('language', 'nl'),
            $request->validated('opponent_username'),
            $request->validated('board_type', 'standard'),
            $request->validated('board_template'),
            $request->boolean('is_public'),
        );

        return GameResource::make($game)
            ->response()
            ->setStatusCode(201);
    }
}
