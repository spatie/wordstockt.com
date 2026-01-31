<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeaderboardResource extends JsonResource
{
    #[\Override]
    public function toArray(Request $request): array
    {
        return [
            'rank' => $this->resource->rank ?? null,
            'ulid' => $this->ulid,
            'username' => $this->username,
            'avatar' => $this->avatar,
            'avatarColor' => $this->avatar_color,
            'eloRating' => $this->elo_rating,
            'gamesPlayed' => $this->games_played,
            'gamesWon' => $this->games_won,
            'winsInPeriod' => $this->when(
                isset($this->wins_in_period),
                $this->wins_in_period
            ),
        ];
    }

    #[\Override]
    public static function collection($resource)
    {
        return parent::collection(
            $resource->map(fn ($user, $index) => tap($user, fn ($u): int|float => $u->rank = $index + 1))
        );
    }
}
