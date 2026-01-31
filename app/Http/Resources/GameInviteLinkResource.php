<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GameInviteLinkResource extends JsonResource
{
    #[\Override]
    public function toArray(Request $request): array
    {
        return [
            'ulid' => $this->ulid,
            'code' => $this->code,
            'url' => $this->getUrl(),
            'game' => $this->formatGame(),
            'inviter' => $this->formatInviter(),
            'is_used' => $this->isUsed(),
            'created_at' => $this->created_at,
        ];
    }

    private function formatGame(): array
    {
        return [
            'ulid' => $this->game->ulid,
            'language' => $this->game->language,
        ];
    }

    private function formatInviter(): array
    {
        return [
            'ulid' => $this->inviter->ulid,
            'username' => $this->inviter->username,
            'avatar' => $this->inviter->avatar,
            'avatar_color' => $this->inviter->avatar_color,
        ];
    }
}
