<?php

namespace App\Domain\User\Actions\Auth;

use App\Domain\User\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SendPasswordResetEmailAction
{
    public function execute(User $user): void
    {
        $token = Str::random(64);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            [
                'token' => hash('sha256', $token),
                'created_at' => now(),
            ]
        );

        $user->sendPasswordResetNotification($token);
    }
}
