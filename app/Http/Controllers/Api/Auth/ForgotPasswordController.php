<?php

namespace App\Http\Controllers\Api\Auth;

use App\Domain\User\Actions\Auth\SendPasswordResetEmailAction;
use App\Domain\User\Models\User;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use Illuminate\Http\JsonResponse;

class ForgotPasswordController
{
    public function __invoke(
        ForgotPasswordRequest $request,
        SendPasswordResetEmailAction $sendPasswordResetEmailAction,
    ): JsonResponse {
        $identifier = $request->validated('identifier');

        $user = User::where('email', $identifier)
            ->orWhere('username', $identifier)
            ->first();

        if ($user) {
            $sendPasswordResetEmailAction->execute($user);
        }

        return response()->json([
            'message' => 'If we have an account with that email or username, we\'ve sent a password reset link.',
        ]);
    }
}
