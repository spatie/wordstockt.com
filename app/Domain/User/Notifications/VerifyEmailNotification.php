<?php

namespace App\Domain\User\Notifications;

use App\Domain\User\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class VerifyEmailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly User $user,
    ) {
        $this->onQueue('notifications');
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $verificationUrl = $this->buildVerificationUrl();

        return (new MailMessage)
            ->subject('Verify your WordStockt email')
            ->greeting("Hello {$this->user->username}!")
            ->line('Please click the button below to verify your email address.')
            ->action('Verify Email', $verificationUrl)
            ->line('This link will expire in 7 days.')
            ->line('If you did not create an account, no further action is required.');
    }

    private function buildVerificationUrl(): string
    {
        return URL::temporarySignedRoute(
            'verification.verify.web',
            now()->addDays(7),
            ['ulid' => $this->user->ulid]
        );
    }
}
