<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FriendResource extends JsonResource
{
    #[\Override]
    public function toArray(Request $request): array
    {
        return [
            'ulid' => $this->ulid,
            'friend_ulid' => $this->friend->ulid,
            'username' => $this->friend->username,
            'avatar' => $this->friend->avatar,
            'avatar_color' => $this->friend->avatar_color,
            'elo_rating' => $this->friend->elo_rating,
            'created_at' => $this->created_at,
        ];
    }
}
