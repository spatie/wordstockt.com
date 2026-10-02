<?php

namespace App\Http\Resources;

use App\Domain\User\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class UserSearchResource extends JsonResource
{
    #[\Override]
    public function toArray(Request $request): array
    {
        return [
            'ulid' => $this->ulid,
            'username' => $this->username,
            'avatar' => $this->avatarUrl(),
            'avatar_color' => $this->avatar_color,
            'eloRating' => $this->elo_rating,
        ];
    }
}
