<?php

namespace App\Domain\Game\Events;

use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\Move;
use App\Domain\User\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class MoveReactionChanged implements ShouldBroadcastNow
{
    use Dispatchable;

    public function __construct(
        public Game $game,
        public Move $move,
        public User $user,
        public ?string $reaction,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('game.'.$this->game->ulid)];
    }

    public function broadcastAs(): string
    {
        return 'move.reaction.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'move_ulid' => $this->move->ulid,
            'user_ulid' => $this->user->ulid,
            'reaction' => $this->reaction,
        ];
    }
}
