<?php

namespace App\Http\Controllers\Api\Auth;

use App\Domain\User\Actions\SetAvatarAction;
use App\Http\Requests\Auth\StoreAvatarRequest;
use App\Http\Resources\UserResource;

class StoreAvatarController
{
    public function __construct(private SetAvatarAction $setAvatar) {}

    public function __invoke(StoreAvatarRequest $request): UserResource
    {
        $user = $request->user();

        $this->setAvatar->execute($user, $request->file('avatar'));

        return new UserResource($user->fresh());
    }
}
