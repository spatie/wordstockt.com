<?php

namespace App\Domain\Support\Listeners;

use App\Domain\User\Models\PushToken;
use Illuminate\Notifications\Events\NotificationFailed;
use NotificationChannels\Expo\ExpoChannel;
use NotificationChannels\Expo\ExpoError;

class HandleFailedExpoNotification
{
    public function handle(NotificationFailed $event): void
    {
        if ($event->channel !== ExpoChannel::NAME) {
            return;
        }

        $error = $event->data;

        if (! $error instanceof ExpoError) {
            return;
        }

        if (! $error->type->isDeviceNotRegistered()) {
            return;
        }

        PushToken::where('token', $error->token->asString())->delete();
    }
}
