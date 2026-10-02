<?php

namespace App\Http\Resources;

use App\Domain\User\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class UserPublicResource extends JsonResource
{
    #[\Override]
    public function toArray(Request $request): array
    {
        return [
            'ulid' => $this->ulid,
            'username' => $this->username,
            'avatar' => $this->avatarUrl(),
            'avatarColor' => $this->avatar_color,
            'eloRating' => $this->elo_rating,
            'gamesPlayed' => $this->games_played,
            'gamesWon' => $this->games_won,
            'winRate' => $this->win_rate,
        ];
    }
}
