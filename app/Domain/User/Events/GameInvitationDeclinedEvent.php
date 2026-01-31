<?php

namespace App\Domain\User\Events;

use App\Domain\User\Models\GameInvitation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GameInvitationDeclinedEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public GameInvitation $invitation
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.'.$this->invitation->inviter->ulid),
            new PrivateChannel('game.'.$this->invitation->game->ulid),
        ];
    }

    public function broadcastAs(): string
    {
        return 'game.invitation.declined';
    }

    public function broadcastWith(): array
    {
        return [
            'game' => [
                'ulid' => $this->invitation->game->ulid,
            ],
            'invitee' => [
                'ulid' => $this->invitation->invitee->ulid,
                'username' => $this->invitation->invitee->username,
            ],
        ];
    }
}
