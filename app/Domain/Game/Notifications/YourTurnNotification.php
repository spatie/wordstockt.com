<?php

namespace App\Domain\Game\Notifications;

use App\Domain\Game\Enums\MoveType;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\Move;
use App\Domain\User\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\Expo\ExpoChannel;
use NotificationChannels\Expo\ExpoMessage;

class YourTurnNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [30, 60, 120];

    public function __construct(
        public readonly Game $game,
        public readonly ?Move $move = null,
        public readonly ?User $madeBy = null,
        public readonly bool $isAutoPass = false,
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
            ->title($this->isFirstTurn() ? 'Game started!' : 'Your turn!')
            ->body($this->buildBody())
            ->data(['game_ulid' => $this->game->ulid])
            ->playSound();
    }

    private function isFirstTurn(): bool
    {
        return $this->move === null;
    }

    private function buildBody(): string
    {
        if ($this->isFirstTurn()) {
            return 'Game started! You can make the first move.';
        }

        return match ($this->move->type) {
            MoveType::Play => $this->buildPlayBody(),
            MoveType::Pass => $this->isAutoPass
                ? "{$this->madeBy->username}'s turn timed out. It's your turn!"
                : "{$this->madeBy->username} passed their turn",
            MoveType::Swap => "{$this->madeBy->username} swapped tiles",
            default => "{$this->madeBy->username} made a move",
        };
    }

    private function buildPlayBody(): string
    {
        $words = collect($this->move->words)->join(', ');
        $score = $this->move->score;

        return "{$this->madeBy->username} played {$words} for {$score} points";
    }
}
