<?php

namespace App\Http\Controllers\Api\Game;

use App\Domain\Game\Actions\DeleteGameAction;
use App\Domain\Game\Models\Game;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DestroyController
{
    public function __invoke(Request $request, Game $game): JsonResponse
    {
        app(DeleteGameAction::class)->execute($game, $request->user());

        return response()->json(['message' => 'Game deleted successfully.']);
    }
}
