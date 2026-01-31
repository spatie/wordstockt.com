<?php

namespace App\Domain\Game\Notifications;

use App\Domain\Game\Models\Game;
use App\Domain\User\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\Expo\ExpoChannel;
use NotificationChannels\Expo\ExpoMessage;

class TurnReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [30, 60, 120];

    public function __construct(
        public readonly Game $game,
        public readonly int $hoursRemaining,
        public readonly User $opponent,
    ) {
        $this->onQueue('notifications');
    }

    public function via(object $notifiable): array
    {
        return [ExpoChannel::class];
    }

    public function toExpo(object $notifiable): ExpoMessage
    {
        /** @var User $notifiable */
        $status = $this->getGameStatus($notifiable);

        return ExpoMessage::create()
            ->title($this->getTitle())
            ->body($this->getBody($status))
            ->data(['game_ulid' => $this->game->ulid])
            ->playSound();
    }

    private function getTitle(): string
    {
        return match ($this->hoursRemaining) {
            24 => "Clock's ticking!",
            4 => "Time's almost up!",
            1 => 'Last chance!',
            default => 'Turn reminder',
        };
    }

    private function getBody(string $status): string
    {
        $opponentName = $this->opponent->username;

        $messages = $this->getStatusMessages($opponentName, $status);

        return $messages[$this->hoursRemaining] ?? $messages['default'];
    }

    /**
     * @return array<int|string, string>
     */
    private function getStatusMessages(string $opponentName, string $status): array
    {
        return match ($status) {
            'winning' => [
                24 => "You're crushing {$opponentName}! Don't let 24 hours slip away - seal the deal!",
                4 => 'Victory is within reach! Only 4 hours left to keep your winning streak alive.',
                1 => "You're SO close to glory! 1 hour left - don't snatch defeat from the jaws of victory!",
                'default' => "Don't lose your lead! Make your move against {$opponentName}.",
            ],
            'losing' => [
                24 => "{$opponentName} thinks they've got this. You have 24 hours to prove them wrong!",
                4 => "4 hours to mount your comeback! {$opponentName} won't see it coming.",
                1 => '1 hour left for a miracle! Every great underdog story starts now.',
                'default' => "Time for a comeback! Make your move against {$opponentName}.",
            ],
            'tied' => [
                24 => "It's anyone's game! 24 hours to break the tie with {$opponentName}.",
                4 => "Neck and neck with {$opponentName}! 4 hours to pull ahead.",
                1 => 'Tied with 1 hour left! This is where legends are made.',
                'default' => "Break the tie! Make your move against {$opponentName}.",
            ],
            default => [
                24 => "24 hours left to make your move against {$opponentName}!",
                4 => "Only 4 hours remaining! {$opponentName} is waiting.",
                1 => '1 hour until your turn expires! Quick, make your move!',
                'default' => "Your turn against {$opponentName} is expiring soon!",
            ],
        };
    }

    private function getGameStatus(User $player): string
    {
        $myScore = $this->game->getPlayerScore($player);
        $opponentScore = $this->game->getPlayerScore($this->opponent);

        if ($myScore > $opponentScore) {
            return 'winning';
        }

        if ($myScore < $opponentScore) {
            return 'losing';
        }

        return 'tied';
    }
}
