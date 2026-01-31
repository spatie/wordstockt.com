<?php

namespace App\Domain\Game\Notifications;

use App\Domain\Game\Models\Game;
use App\Domain\User\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\Expo\ExpoChannel;
use NotificationChannels\Expo\ExpoMessage;

class GameFinishedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [30, 60, 120];

    public function __construct(
        public readonly Game $game,
        public readonly bool $wasResign = false,
        public readonly ?User $resignedPlayer = null,
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
            ->title('Game finished!')
            ->body($this->buildBody($notifiable))
            ->data(['game_ulid' => $this->game->ulid])
            ->playSound();
    }

    private function buildBody(User $notifiable): string
    {
        if ($this->wasResign && $this->resignedPlayer) {
            return "{$this->resignedPlayer->username} resigned. You win!";
        }

        $opponent = $this->game->getOpponent($notifiable);
        $myScore = $this->game->getPlayerScore($notifiable);
        $opponentScore = $opponent instanceof \App\Domain\User\Models\User ? $this->game->getPlayerScore($opponent) : 0;

        if ($this->game->isWinner($notifiable)) {
            return "You beat {$opponent?->username}! Final: {$myScore} - {$opponentScore}";
        }

        return "{$opponent?->username} wins. Final: {$myScore} - {$opponentScore}";
    }
}
