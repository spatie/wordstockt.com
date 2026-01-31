<?php

namespace App\Http\Controllers\Api\Auth;

use App\Domain\User\Actions\Auth\SendVerificationEmailAction;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Http\Resources\UserResource;

class UpdateUserController
{
    public function __invoke(UpdateProfileRequest $request): UserResource
    {
        $user = $request->user();
        $validated = $request->validated();

        $emailChanged = isset($validated['email']) && $validated['email'] !== $user->email;

        if ($emailChanged) {
            $validated['email_verified_at'] = null;
        }

        $user->update($validated);

        if ($emailChanged) {
            app(SendVerificationEmailAction::class)->execute($user);
        }

        return new UserResource($user->fresh());
    }
}
