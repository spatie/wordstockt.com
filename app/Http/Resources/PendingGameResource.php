<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PendingGameResource extends JsonResource
{
    #[\Override]
    public function toArray(Request $request): array
    {
        return [
            'ulid' => $this->ulid,
            'language' => $this->language,
            'creator' => $this->players->first()?->username,
            'max_players' => $this->max_players,
            'players_joined' => $this->gamePlayers->count(),
            'created_at' => $this->created_at,
        ];
    }
}
