<?php

namespace App\Http\Controllers\Api\Auth;

use App\Domain\User\Actions\Auth\ConvertGuestToUserAction;
use App\Http\Requests\Auth\ConvertGuestRequest;
use App\Http\Resources\UserResource;

class ConvertGuestController
{
    public function __invoke(ConvertGuestRequest $request, ConvertGuestToUserAction $action): UserResource
    {
        $user = $action->execute(
            $request->user(),
            $request->validated('username'),
            $request->validated('email'),
            $request->validated('password'),
        );

        return new UserResource($user);
    }
}
