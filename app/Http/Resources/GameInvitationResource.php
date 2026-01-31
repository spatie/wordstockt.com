<?php

namespace App\Http\Resources;

use App\Domain\User\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GameInvitationResource extends JsonResource
{
    #[\Override]
    public function toArray(Request $request): array
    {
        return [
            'ulid' => $this->ulid,
            'status' => $this->status->value,
            'game' => $this->formatGame(),
            'inviter' => $this->formatUser($this->inviter),
            'invitee' => $this->formatUser($this->invitee),
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

    private function formatUser(User $user): array
    {
        return [
            'ulid' => $user->ulid,
            'username' => $user->username,
            'avatar' => $user->avatar,
            'avatar_color' => $user->avatar_color,
        ];
    }
}
