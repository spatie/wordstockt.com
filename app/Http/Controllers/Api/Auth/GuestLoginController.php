<?php

namespace App\Http\Controllers\Api\Auth;

use App\Domain\User\Actions\Auth\CreateGuestUserAction;
use App\Http\Resources\UserResource;
use Symfony\Component\HttpFoundation\Response;

class GuestLoginController
{
    public function __invoke(CreateGuestUserAction $action): Response
    {
        $user = $action->execute();

        $token = $user->createToken('auth-token')->plainTextToken;

        return (new UserResource($user))
            ->additional(['token' => $token])
            ->response()
            ->setStatusCode(201);
    }
}
