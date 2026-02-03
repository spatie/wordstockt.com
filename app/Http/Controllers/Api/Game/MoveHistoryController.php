<?php

namespace App\Http\Controllers\Api\Game;

use App\Domain\Game\Models\Game;
use App\Http\Requests\Game\ShowRequest;
use App\Http\Resources\MoveHistoryResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MoveHistoryController
{
    public function __invoke(ShowRequest $request, Game $game): AnonymousResourceCollection
    {
        $moves = $game->moves()
            ->with('user')
            ->latest()
            ->get();

        return MoveHistoryResource::collection($moves);
    }
}
