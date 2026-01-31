<?php

namespace App\Domain\Game\Notifications;

use App\Domain\Game\Models\Game;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\Expo\ExpoChannel;
use NotificationChannels\Expo\ExpoMessage;

class TurnTimedOutNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [30, 60, 120];

    public function __construct(
        public readonly Game $game,
    ) {
        $this->onQueue('notifications');
    }

    public function via(object $notifiable): array
    {
        return [ExpoChannel::class];
    }

    public function toExpo(object $notifiable): ExpoMessage
    {
        $message = $this->game->isFinished()
            ? 'Time ran out and the game has ended. Better luck next time!'
            : "Your 72-hour timer expired and you've automatically passed. It's your opponent's turn now.";

        return ExpoMessage::create()
            ->title('Turn timed out')
            ->body($message)
            ->data(['game_ulid' => $this->game->ulid])
            ->playSound();
    }
}
