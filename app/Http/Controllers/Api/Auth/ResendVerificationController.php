<?php

namespace App\Http\Controllers\Api\Auth;

use App\Domain\User\Actions\Auth\SendVerificationEmailAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ResendVerificationController
{
    public function __invoke(
        Request $request,
        SendVerificationEmailAction $sendVerificationEmailAction
    ): JsonResponse {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email already verified.',
            ]);
        }

        $sendVerificationEmailAction->execute($user);

        return response()->json([
            'message' => 'Verification email sent.',
        ]);
    }
}
