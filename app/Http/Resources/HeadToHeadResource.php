<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HeadToHeadResource extends JsonResource
{
    #[\Override]
    public function toArray(Request $request): array
    {
        return [
            'opponentUlid' => $this->opponent->ulid,
            'opponentUsername' => $this->opponent->username,
            'opponentAvatar' => $this->opponent->avatar,
            'opponentAvatarColor' => $this->opponent->avatar_color,
            'wins' => $this->wins,
            'losses' => $this->losses,
            'draws' => $this->draws,
            'totalGames' => $this->total_games,
            'winRate' => $this->win_rate,
            'averageScoreDifference' => $this->average_score_difference,
            'bestWord' => $this->best_word ? [
                'word' => $this->best_word,
                'score' => $this->best_word_score,
            ] : null,
        ];
    }
}
