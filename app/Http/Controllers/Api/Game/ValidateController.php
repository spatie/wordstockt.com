<?php

namespace App\Http\Controllers\Api\Game;

use App\Domain\Game\Data\Move;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Support\ValidationService;
use App\Http\Requests\Game\ValidateMoveRequest;
use Illuminate\Http\JsonResponse;

class ValidateController
{
    public function __invoke(ValidateMoveRequest $request, Game $game): JsonResponse
    {
        $moveData = Move::fromArray($request->validated('tiles'));
        $result = app(ValidationService::class)->validatePlacement($game, $moveData);

        return response()->json($result);
    }
}
