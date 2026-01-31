<?php

namespace App\Domain\Game\Events;

use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\Move;
use App\Domain\User\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MovePlayed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Game $game,
        public Move $move,
        public User $player
    ) {}

    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel('game.'.$this->game->ulid),
        ];

        // Also notify the opponent via their user channel for list updates
        $opponent = $this->game->gamePlayers
            ->first(fn ($gp) => $gp->user_id !== $this->player->id)
            ?->user;

        if ($opponent) {
            $channels[] = new PrivateChannel('user.'.$opponent->ulid);
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'move.played';
    }

    public function broadcastWith(): array
    {
        return [
            'move' => [
                'ulid' => $this->move->ulid,
                'player_ulid' => $this->player->ulid,
                'player_username' => $this->player->username,
                'tiles' => $this->move->tiles,
                'words' => $this->move->words,
                'score' => $this->move->score,
                'type' => $this->move->type->value,
                'created_at' => $this->move->created_at,
            ],
            'game' => [
                'ulid' => $this->game->ulid,
                'board' => $this->game->board_state,
                'status' => $this->game->status->value,
                'current_turn_user_ulid' => $this->game->currentTurnUser?->ulid,
                'winner_ulid' => $this->game->winner?->ulid,
                'tiles_remaining' => count($this->game->tile_bag ?? []),
                'players' => $this->game->gamePlayers->map(fn ($gp): array => [
                    'user_ulid' => $gp->user->ulid,
                    'score' => $gp->score,
                    'rack_count' => count($gp->rack_tiles ?? []),
                ]),
            ],
        ];
    }
}
