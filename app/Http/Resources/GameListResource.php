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
        $myGamePlayer = $this->gamePlayers->firstWhere('user_id', $user->id);

        // Backwards compatibility: clients on the pre-multiplayer app version
        // require `opponent`/`opponent_score`. Keep emitting them (first other
        // player) alongside the N-player `players` array so old apps keep
        // parsing the games list.
        $opponent = $this->players->firstWhere('id', '!=', $user->id);
        $opponentGamePlayer = $opponent
            ? $this->gamePlayers->firstWhere('user_id', $opponent->id)
            : null;

        return [
            'ulid' => $this->ulid,
            'language' => $this->language,
            'status' => $this->status->value,
            'max_players' => $this->max_players,
            'opponent' => $opponent ? [
                'ulid' => $opponent->ulid,
                'username' => $opponent->username,
                'avatar' => $opponent->avatarUrl(),
                'avatar_color' => $opponent->avatar_color,
            ] : null,
            'opponent_score' => $opponentGamePlayer?->score ?? 0,
            'players' => $this->gamePlayers
                ->sortBy('turn_order')
                ->values()
                ->map(fn ($gp): array => [
                    'ulid' => $gp->user->ulid,
                    'username' => $gp->user->username,
                    'avatar' => $gp->user->avatarUrl(),
                    'avatar_color' => $gp->user->avatar_color,
                    'score' => $gp->score,
                    'is_current_turn' => $this->current_turn_user_id === $gp->user_id,
                    'is_me' => $gp->user_id === $user->id,
                    'has_left' => $gp->hasLeft(),
                ]),
            'my_score' => $myGamePlayer?->score ?? 0,
            'is_my_turn' => $this->current_turn_user_id === $user->id,
            'winner_ulid' => $this->winner?->ulid,
            'updated_at' => $this->updated_at,
            'last_move_description' => $this->resource->getLastMoveDescription($user, $this->resource->getOpponent($user)),
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
                'avatar' => $invitation->invitee->avatarUrl(),
                'avatar_color' => $invitation->invitee->avatar_color,
            ],
        ];
    }
}
