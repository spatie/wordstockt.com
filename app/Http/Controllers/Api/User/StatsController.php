<?php

namespace App\Http\Controllers\Api\User;

use App\Domain\User\Models\User;
use App\Http\Resources\UserStatsResource;

class StatsController
{
    public function __invoke(User $user): \App\Http\Resources\UserStatsResource
    {
        $user->load('statistics');

        return new UserStatsResource($user);
    }
}
