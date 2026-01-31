<?php

namespace App\Domain\User\Actions\Auth;

use App\Domain\User\Models\User;

class SendVerificationEmailAction
{
    public function execute(User $user): void
    {
        if ($user->hasVerifiedEmail()) {
            return;
        }

        $user->sendEmailVerificationNotification();
    }
}
