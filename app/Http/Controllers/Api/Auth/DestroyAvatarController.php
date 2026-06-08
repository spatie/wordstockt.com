<?php

namespace App\Http\Controllers\Api\Auth;

use App\Domain\User\Actions\RemoveAvatarAction;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;

class DestroyAvatarController
{
    public function __construct(private RemoveAvatarAction $removeAvatar) {}

    public function __invoke(Request $request): UserResource
    {
        $user = $request->user();

        $this->removeAvatar->execute($user);

        return new UserResource($user->fresh());
    }
}
