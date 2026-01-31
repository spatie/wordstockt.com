<?php

namespace App\Domain\User\Events;

use App\Domain\User\Models\GameInvitation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GameInvitationEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public GameInvitation $invitation
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.'.$this->invitation->invitee->ulid),
        ];
    }

    public function broadcastAs(): string
    {
        return 'game.invitation';
    }

    public function broadcastWith(): array
    {
        return [
            'ulid' => $this->invitation->ulid,
            'game' => [
                'ulid' => $this->invitation->game->ulid,
                'language' => $this->invitation->game->language,
            ],
            'inviter' => [
                'ulid' => $this->invitation->inviter->ulid,
                'username' => $this->invitation->inviter->username,
                'avatar' => $this->invitation->inviter->avatar,
                'avatar_color' => $this->invitation->inviter->avatar_color,
            ],
        ];
    }
}
