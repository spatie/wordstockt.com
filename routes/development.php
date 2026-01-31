<?php

use App\Domain\User\Mail\ResetPasswordMail;
use App\Domain\User\Models\User;
use App\Domain\User\Notifications\VerifyEmailNotification;
use Illuminate\Support\Facades\Route;

Route::prefix('dev/mail')->group(function (): void {
    Route::get('/', fn () => view('dev.mail-index', [
        'mails' => [
            'reset-password' => 'Reset Password',
            'verify-email' => 'Verify Email',
        ],
    ]));

    Route::get('/reset-password', function () {
        $user = User::first() ?? new User([
            'username' => 'testuser',
            'email' => 'test@example.com',
        ]);

        return new ResetPasswordMail(
            token: 'sample-reset-token-12345',
            user: $user,
        );
    });

    Route::get('/verify-email', function () {
        $user = User::first() ?? new User([
            'username' => 'testuser',
            'email' => 'test@example.com',
            'ulid' => '01HTEST123456789ABCDEF',
        ]);

        $notification = new VerifyEmailNotification($user);

        return $notification->toMail($user);
    });
});
