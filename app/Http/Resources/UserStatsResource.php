<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserStatsResource extends JsonResource
{
    #[\Override]
    public function toArray(Request $request): array
    {
        $stats = $this->statistics;

        return [
            'ulid' => $this->ulid,
            'username' => $this->username,
            'avatar' => $this->avatar,

            'eloRating' => $this->elo_rating,
            'gamesPlayed' => $stats->games_played ?? 0,
            'gamesWon' => $stats->games_won ?? 0,
            'gamesLost' => $stats->games_lost ?? 0,
            'gamesDraw' => $stats->games_draw ?? 0,
            'winRate' => $stats->win_rate ?? 0,

            'highestScoringWord' => $stats?->highest_scoring_word ? [
                'word' => $stats->highest_scoring_word,
                'score' => $stats->highest_scoring_word_score,
            ] : null,
            'highestScoringMove' => $stats->highest_scoring_move ?? 0,
            'bingosCount' => $stats->bingos_count ?? 0,
            'totalWordsPlayed' => $stats->total_words_played ?? 0,
            'totalPointsScored' => $stats->total_points_scored ?? 0,

            'highestGameScore' => $stats->highest_game_score ?? 0,
            'averageGameScore' => $stats->average_game_score ?? 0,
            'currentWinStreak' => $stats->current_win_streak ?? 0,
            'bestWinStreak' => $stats->best_win_streak ?? 0,
            'biggestComeback' => $stats->biggest_comeback ?? 0,
            'closestVictory' => $stats?->closest_victory,

            'tripleWordTilesUsed' => $stats->triple_word_tiles_used ?? 0,
            'doubleWordTilesUsed' => $stats->double_word_tiles_used ?? 0,
            'blankTilesPlayed' => $stats->blank_tiles_played ?? 0,

            'firstMoveWinRate' => $stats->first_move_win_rate ?? 0,

            'highestEloEver' => $stats->highest_elo_ever ?? 1200,
            'lowestEloEver' => $stats->lowest_elo_ever ?? 1200,
        ];
    }
}
