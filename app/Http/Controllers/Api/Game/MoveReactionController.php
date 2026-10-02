<?php

namespace App\Http\Controllers\Api\Game;

use App\Domain\Game\Events\MoveReactionChanged;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\Move;
use App\Http\Requests\Game\MoveReactionRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MoveReactionController
{
    public function update(MoveReactionRequest $request, Game $game, Move $move): JsonResponse
    {
        $reaction = $request->validated('reaction');

        $move->reactions()->updateOrCreate(
            ['user_id' => $request->user()->id],
            ['reaction' => $reaction],
        );

        MoveReactionChanged::dispatch($game, $move, $request->user(), $reaction);

        return response()->json(['reaction' => $reaction]);
    }

    public function destroy(Request $request, Game $game, Move $move): JsonResponse
    {
        abort_unless($request->user()->can('play', $game), 403);
        abort_unless($move->game_id === $game->id, 403);
        abort_if($move->user_id === $request->user()->id, 403);

        $deleted = $move->reactions()->where('user_id', $request->user()->id)->delete();

        if ($deleted) {
            MoveReactionChanged::dispatch($game, $move, $request->user(), null);
        }

        return response()->json(['reaction' => null]);
    }
}
