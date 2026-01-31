<?php

namespace App\Http\Controllers\Api\Auth;

use App\Domain\User\Models\User;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController
{
    public function __invoke(LoginRequest $request)
    {
        $identifier = $request->validated('email');
        $password = $request->validated('password');

        $user = $this->findUserByIdentifier($identifier);

        if (! $user || ! Auth::attempt(['email' => $user->email, 'password' => $password])) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return new UserResource($user)->additional(['token' => $token]);
    }

    private function findUserByIdentifier(string $identifier): ?User
    {
        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            return User::where('email', $identifier)->first();
        }

        return User::where('username', $identifier)->first();
    }
}
