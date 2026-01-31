<?php

namespace App\Domain\User\Actions\Auth;

use App\Domain\User\Models\User;
use Illuminate\Support\Facades\Hash;

class ConvertGuestToUserAction
{
    public function execute(User $user, string $username, string $email, string $password): User
    {
        $user->update([
            'username' => $username,
            'email' => $email,
            'password' => Hash::make($password),
            'is_guest' => false,
        ]);

        app(SendVerificationEmailAction::class)->execute($user);

        return $user->fresh();
    }
}
