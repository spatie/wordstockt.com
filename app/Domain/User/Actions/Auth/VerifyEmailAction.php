<?php

namespace App\Domain\User\Actions\Auth;

use App\Domain\User\Models\User;

class VerifyEmailAction
{
    public function execute(User $user): bool
    {
        if ($user->hasVerifiedEmail()) {
            return false;
        }

        $user->markEmailAsVerified();

        return true;
    }
}
