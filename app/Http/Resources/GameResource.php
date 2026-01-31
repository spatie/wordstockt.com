<?php

namespace App\Http\Resources;

use App\Domain\Game\Support\Board;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GameResource extends JsonResource
{
    #[\Override]
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $gamePlayer = $this->resource->getGamePlayer($user);

        return [
            'ulid' => $this->ulid,
            'language' => $this->language,
            'status' => $this->status->value,
            'board' => $this->board_state,
            'board_template' => $this->board_template ?? app(Board::class)->getBoardTemplate(),
            'players' => $this->gamePlayers->map(fn ($gp): array => [
                'ulid' => $gp->user->ulid,
                'username' => $gp->user->username,
                'avatar' => $gp->user->avatar,
                'avatar_color' => $gp->user->avatar_color,
                'score' => $gp->score,
                'rack_count' => $gp->getRackTileCount(),
                'is_current_turn' => $this->current_turn_user_id === $gp->user_id,
                'has_free_swap' => $gp->has_free_swap,
                'has_received_blank' => $gp->has_received_blank,
                'received_empty_rack_bonus' => $gp->received_empty_rack_bonus,
            ]),
            'my_rack' => $gamePlayer?->rack_tiles ?? [],
            'tiles_remaining' => count($this->tile_bag ?? []),
            'current_turn_user_ulid' => $this->currentTurnUser?->ulid,
            'winner_ulid' => $this->winner?->ulid,
            'is_last_move' => $this->resource->isLastMoveForPlayer($user),
            'last_move' => $this->latestMove
                ? $this->formatLastMove($this->latestMove)
                : null,
            'turn_expires_at' => $this->resource->getTurnExpiresAt()?->toISOString(),
            'pending_invitation' => $this->formatPendingInvitation(),
            'is_public' => $this->is_public,
            'can_join' => $this->resource->canBeJoinedBy($user),
        ];
    }

    private function formatLastMove($move): array
    {
        return [
            'ulid' => $move->ulid,
            'user_ulid' => $move->user->ulid,
            'type' => $move->type->value,
            'words' => $move->words,
            'score' => $move->score,
            'tiles' => $move->tiles,
            'created_at' => $move->created_at->toISOString(),
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
