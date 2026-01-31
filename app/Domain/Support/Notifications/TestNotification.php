<?php

namespace App\Domain\Support\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\Expo\ExpoChannel;
use NotificationChannels\Expo\ExpoMessage;

class TestNotification extends Notification
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return [ExpoChannel::class];
    }

    public function toExpo(object $notifiable): ExpoMessage
    {
        return ExpoMessage::create()
            ->title('Test Notification')
            ->body('This is a test notification from WordStockt!')
            ->playSound();
    }
}
