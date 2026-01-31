<?php

namespace App\Domain\User\Events;

use App\Domain\Game\Models\Game;
use App\Domain\User\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GameInvitationAcceptedEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Game $game,
        public User $inviter,
        public User $accepter
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.'.$this->inviter->ulid),
            new PrivateChannel('game.'.$this->game->ulid),
        ];
    }

    public function broadcastAs(): string
    {
        return 'game.invitation.accepted';
    }

    public function broadcastWith(): array
    {
        return [
            'game' => [
                'ulid' => $this->game->ulid,
                'language' => $this->game->language,
            ],
            'accepter' => [
                'ulid' => $this->accepter->ulid,
                'username' => $this->accepter->username,
                'avatar' => $this->accepter->avatar,
            ],
        ];
    }
}
