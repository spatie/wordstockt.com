<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EloHistoryResource extends JsonResource
{
    #[\Override]
    public function toArray(Request $request): array
    {
        return [
            'gameUlid' => $this->game?->ulid,
            'eloBefore' => $this->elo_before,
            'eloAfter' => $this->elo_after,
            'eloChange' => $this->elo_change,
            'timestamp' => $this->created_at->toIso8601String(),
        ];
    }
}
