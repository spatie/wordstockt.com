<?php

namespace App\Domain\Game\Events;

use App\Domain\Game\Models\Game;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GameStarted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Game $game) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('game.'.$this->game->ulid),
        ];
    }

    public function broadcastAs(): string
    {
        return 'game.started';
    }

    /**
     * @return array{game: array{ulid: string, status: string}}
     */
    public function broadcastWith(): array
    {
        return [
            'game' => [
                'ulid' => $this->game->ulid,
                'status' => $this->game->status->value,
            ],
        ];
    }
}
