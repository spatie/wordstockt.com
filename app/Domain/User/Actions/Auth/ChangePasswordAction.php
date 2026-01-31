<?php

namespace App\Domain\User\Actions\Auth;

use App\Domain\User\Models\User;

class ChangePasswordAction
{
    public function execute(User $user, string $newPassword): void
    {
        $user->update([
            'password' => $newPassword,
        ]);
    }
}
