<?php

namespace App\Http\Controllers\Api\User;

use App\Domain\User\Models\User;
use App\Http\Resources\HeadToHeadResource;

class HeadToHeadController
{
    public function __invoke(User $user)
    {
        $h2hStats = $user->headToHeadStats()
            ->with('opponent:id,ulid,username,avatar')
            ->orderByDesc('wins')
            ->get();

        return HeadToHeadResource::collection($h2hStats);
    }
}
