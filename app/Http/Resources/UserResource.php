<?php

namespace App\Http\Resources;

use App\Domain\User\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class UserResource extends JsonResource
{
    #[\Override]
    public function toArray(Request $request): array
    {
        return [
            'ulid' => $this->ulid,
            'username' => $this->username,
            'email' => $this->email,
            'avatar' => $this->avatar,
            'avatarColor' => $this->avatar_color,
            'eloRating' => $this->elo_rating,
            'gamesPlayed' => $this->games_played,
            'gamesWon' => $this->games_won,
            'isGuest' => $this->is_guest,
            'emailVerifiedAt' => $this->email_verified_at?->toIso8601String(),
            'createdAt' => $this->created_at->toIso8601String(),
        ];
    }
}
