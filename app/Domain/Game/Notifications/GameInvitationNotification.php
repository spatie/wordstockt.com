<?php

namespace App\Domain\Game\Notifications;

use App\Domain\Game\Models\Game;
use App\Domain\User\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\Expo\ExpoChannel;
use NotificationChannels\Expo\ExpoMessage;

class GameInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [30, 60, 120];

    public function __construct(
        public readonly Game $game,
        public readonly User $inviter,
    ) {
        $this->onQueue('notifications');
    }

    public function via(object $notifiable): array
    {
        return [ExpoChannel::class];
    }

    public function toExpo(object $notifiable): ExpoMessage
    {
        return ExpoMessage::create()
            ->title('Game invitation')
            ->body("{$this->inviter->username} invited you to play!")
            ->data([
                'type' => 'invitation',
                'game_ulid' => $this->game->ulid,
            ])
            ->playSound();
    }
}
