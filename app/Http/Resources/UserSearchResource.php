<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserSearchResource extends JsonResource
{
    #[\Override]
    public function toArray(Request $request): array
    {
        return [
            'ulid' => $this->ulid,
            'username' => $this->username,
            'avatar' => $this->avatar,
            'avatar_color' => $this->avatar_color,
            'eloRating' => $this->elo_rating,
        ];
    }
}
