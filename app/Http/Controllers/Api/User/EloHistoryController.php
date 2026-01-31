<?php

namespace App\Http\Controllers\Api\User;

use App\Domain\User\Models\User;
use App\Http\Resources\EloHistoryResource;
use Illuminate\Http\Request;

class EloHistoryController
{
    public function __invoke(User $user, Request $request)
    {
        $limit = $request->integer('limit', 50);

        $history = $user->eloHistory()
            ->with('game:id,ulid')
            ->limit($limit)
            ->get();

        return EloHistoryResource::collection($history);
    }
}
