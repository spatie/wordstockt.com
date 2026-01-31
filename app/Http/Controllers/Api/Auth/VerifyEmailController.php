<?php

namespace App\Http\Controllers\Api\Auth;

use App\Domain\User\Actions\Auth\VerifyEmailAction;
use App\Domain\User\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VerifyEmailController
{
    public function __invoke(
        Request $request,
        string $ulid,
        VerifyEmailAction $verifyEmailAction
    ): JsonResponse {
        if (! $request->hasValidSignature()) {
            return response()->json([
                'message' => 'Invalid or expired verification link.',
                'verified' => false,
            ], 403);
        }

        $user = User::where('ulid', $ulid)->first();

        if (! $user) {
            return response()->json([
                'message' => 'User not found.',
                'verified' => false,
            ], 404);
        }

        $wasVerified = $verifyEmailAction->execute($user);

        return response()->json([
            'message' => $wasVerified
                ? 'Email verified successfully!'
                : 'Email was already verified.',
            'verified' => true,
        ]);
    }
}
