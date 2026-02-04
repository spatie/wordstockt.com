<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MoveHistoryResource extends JsonResource
{
    #[\Override]
    public function toArray(Request $request): array
    {
        return [
            'ulid' => $this->ulid,
            'type' => $this->type->value,
            'user' => [
                'ulid' => $this->user->ulid,
                'username' => $this->user->username,
                'avatar' => $this->user->avatar,
                'avatar_color' => $this->user->avatar_color,
            ],
            'words' => $this->words,
            'score' => $this->score,
            'score_breakdown' => $this->score_breakdown,
            'tiles_count' => $this->tiles ? count($this->tiles) : 0,
            'tiles' => $this->tiles,
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
