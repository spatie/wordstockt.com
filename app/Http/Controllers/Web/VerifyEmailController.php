<?php

namespace App\Http\Controllers\Web;

use App\Domain\User\Actions\Auth\VerifyEmailAction;
use App\Domain\User\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VerifyEmailController
{
    public function __invoke(
        Request $request,
        string $ulid,
        VerifyEmailAction $verifyEmailAction
    ): View {
        if (! $request->hasValidSignature()) {
            return view('auth.verify-email', [
                'success' => false,
                'message' => 'This verification link is invalid or has expired.',
            ]);
        }

        $user = User::where('ulid', $ulid)->first();

        if (! $user) {
            return view('auth.verify-email', [
                'success' => false,
                'message' => 'User not found.',
            ]);
        }

        $wasVerified = $verifyEmailAction->execute($user);

        return view('auth.verify-email', [
            'success' => true,
            'message' => $wasVerified
                ? 'Your email has been verified successfully!'
                : 'Your email was already verified.',
            'username' => $user->username,
        ]);
    }
}
