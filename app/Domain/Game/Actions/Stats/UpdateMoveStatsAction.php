<?php

declare(strict_types=1);

namespace App\Domain\Game\Actions\Stats;

use App\Domain\Game\Enums\SquareType;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\HeadToHeadStats;
use App\Domain\Game\Models\Move;
use App\Domain\Game\Support\Board;
use App\Domain\Game\Support\Scoring\ScoringResult;
use App\Domain\User\Models\User;
use App\Domain\User\Models\UserStatistics;
use Illuminate\Support\Collection;

class UpdateMoveStatsAction
{
    public function __construct(
        private readonly Board $board,
    ) {}

    public function execute(User $user, Move $move, Game $game, ScoringResult $scoringResult): void
    {
        $stats = UserStatistics::firstOrCreate(['user_id' => $user->id]);
        $wordScores = $scoringResult->getWordScores();
        $tiles = $move->tiles ?? [];

        $this->updateWordRecords($stats, $wordScores);
        $this->updateMoveRecord($stats, $move->score);
        $this->updateCounters($stats, $scoringResult, $tiles, $move->score);
        $this->updateFirstMoveTracking($stats, $game, $user);

        $stats->save();

        $this->updateHeadToHeadBestWord($user, $game, $wordScores);
    }

    /**
     * @param  Collection<int, array{word: string, baseScore: int, multipliedScore: int, multipliers: array}>  $wordScores
     */
    private function updateWordRecords(UserStatistics $stats, Collection $wordScores): void
    {
        if ($wordScores->isEmpty()) {
            return;
        }

        foreach ($wordScores as $wordData) {
            $word = $wordData['word'];
            $score = $wordData['multipliedScore'];

            if ($score > $stats->highest_scoring_word_score) {
                $stats->highest_scoring_word = strtoupper($word);
                $stats->highest_scoring_word_score = $score;
            }

            $length = mb_strlen($word);
            if ($length > $stats->longest_word_length) {
                $stats->longest_word = strtoupper($word);
                $stats->longest_word_length = $length;
            }
        }
    }

    private function updateMoveRecord(UserStatistics $stats, int $moveScore): void
    {
        if ($moveScore > $stats->highest_scoring_move) {
            $stats->highest_scoring_move = $moveScore;
        }
    }

    private function updateCounters(UserStatistics $stats, ScoringResult $scoringResult, array $tiles, int $moveScore): void
    {
        $wordScores = $scoringResult->getWordScores();

        $stats->total_words_played += $wordScores->count();
        $stats->total_points_scored += $moveScore;

        if ($scoringResult->hasBonus('bingo_bonus')) {
            $stats->bingos_count++;
        }

        $stats->blank_tiles_played += collect($tiles)
            ->filter(fn ($tile) => $tile['is_blank'] ?? false)
            ->count();

        foreach ($tiles as $tile) {
            $squareType = $this->board->getSquareType($tile['x'], $tile['y']);

            if ($squareType === SquareType::TripleWord) {
                $stats->triple_word_tiles_used++;
            } elseif ($squareType === SquareType::DoubleWord) {
                $stats->double_word_tiles_used++;
            }
        }
    }

    private function updateFirstMoveTracking(UserStatistics $stats, Game $game, User $user): void
    {
        $userMoveCount = $game->moves()->where('user_id', $user->id)->count();

        if ($userMoveCount !== 1) {
            return;
        }

        $totalMoveCount = $game->moves()->count();

        if ($totalMoveCount === 1) {
            $stats->first_moves_played++;
        }
    }

    /**
     * @param  Collection<int, array{word: string, baseScore: int, multipliedScore: int, multipliers: array}>  $wordScores
     */
    private function updateHeadToHeadBestWord(User $user, Game $game, Collection $wordScores): void
    {
        $opponent = $game->getOpponent($user);

        if (! $opponent || $wordScores->isEmpty()) {
            return;
        }

        $bestWord = $wordScores->sortByDesc('multipliedScore')->first();

        $h2h = HeadToHeadStats::firstOrCreate(
            ['user_id' => $user->id, 'opponent_id' => $opponent->id],
            ['wins' => 0, 'losses' => 0, 'draws' => 0]
        );

        if ($bestWord['multipliedScore'] > $h2h->best_word_score) {
            $h2h->best_word = strtoupper($bestWord['word']);
            $h2h->best_word_score = $bestWord['multipliedScore'];
            $h2h->save();
        }
    }
}
