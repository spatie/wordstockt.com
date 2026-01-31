<?php

namespace App\Http\Controllers\Api\User;

use App\Domain\User\Models\User;
use App\Http\Resources\UserPublicResource;

class ShowController
{
    public function __invoke(User $user): \App\Http\Resources\UserPublicResource
    {
        return new UserPublicResource($user);
    }
}
