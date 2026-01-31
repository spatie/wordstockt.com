<?php

namespace App\Http\Controllers\Api\Auth;

use App\Domain\User\Actions\Auth\SendVerificationEmailAction;
use App\Domain\User\AvatarColors;
use App\Domain\User\Models\User;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Hash;

class RegisterController
{
    public function __invoke(RegisterRequest $request): \Symfony\Component\HttpFoundation\Response
    {
        $user = User::create([
            'username' => $request->validated('username'),
            'email' => $request->validated('email'),
            'password' => Hash::make($request->validated('password')),
            'avatar_color' => AvatarColors::random(),
        ]);

        $token = $user->createToken('auth-token')->plainTextToken;

        app(SendVerificationEmailAction::class)->execute($user);

        return new UserResource($user)
            ->additional(['token' => $token])
            ->response()
            ->setStatusCode(201);
    }
}
