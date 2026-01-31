<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GameListResource extends JsonResource
{
    #[\Override]
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $opponent = $this->players->firstWhere('id', '!=', $user->id);
        $myGamePlayer = $this->gamePlayers->firstWhere('user_id', $user->id);
        $opponentGamePlayer = $opponent ? $this->gamePlayers->firstWhere('user_id', $opponent->id) : null;

        return [
            'ulid' => $this->ulid,
            'language' => $this->language,
            'status' => $this->status->value,
            'opponent' => $opponent ? [
                'ulid' => $opponent->ulid,
                'username' => $opponent->username,
                'avatar' => $opponent->avatar,
                'avatar_color' => $opponent->avatar_color,
            ] : null,
            'my_score' => $myGamePlayer?->score ?? 0,
            'opponent_score' => $opponentGamePlayer?->score ?? 0,
            'is_my_turn' => $this->current_turn_user_id === $user->id,
            'winner_ulid' => $this->winner?->ulid,
            'updated_at' => $this->updated_at,
            'last_move_description' => $this->resource->getLastMoveDescription($user, $opponent),
            'turn_expires_at' => $this->resource->getTurnExpiresAt()?->toISOString(),
            'pending_invitation' => $this->formatPendingInvitation(),
            'is_public' => $this->is_public,
        ];
    }

    private function formatPendingInvitation(): ?array
    {
        $invitation = $this->pendingInvitation;

        if (! $invitation) {
            return null;
        }

        return [
            'ulid' => $invitation->ulid,
            'invitee' => [
                'ulid' => $invitation->invitee->ulid,
                'username' => $invitation->invitee->username,
                'avatar' => $invitation->invitee->avatar,
                'avatar_color' => $invitation->invitee->avatar_color,
            ],
        ];
    }
}
