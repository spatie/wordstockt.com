<?php

declare(strict_types=1);

namespace App\Domain\Game\Actions\Stats;

use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\GamePlayer;
use App\Domain\Game\Models\HeadToHeadStats;
use App\Domain\User\Models\EloHistory;
use App\Domain\User\Models\User;
use App\Domain\User\Models\UserStatistics;
use App\Domain\User\Support\EloCalculator\EloCalculator;
use Illuminate\Support\Collection;

class UpdateGameEndStatsAction
{
    /** @var array<int, UserStatistics> */
    private array $statsCache = [];

    public function __construct(
        private readonly EloCalculator $eloCalculator,
    ) {}

    public function execute(Game $game): void
    {
        $gamePlayers = $game->gamePlayers()->with('user')->get();

        if ($gamePlayers->count() !== 2) {
            return;
        }

        $winner = $game->winner;

        $this->updatePlayerStats($gamePlayers, $winner);

        if ($winner) {
            $this->updateWinnerSpecificStats($game, $gamePlayers, $winner);
        }

        $this->updateEloRatings($game, $gamePlayers, $winner);
        $this->updateHeadToHeadRecords($gamePlayers, $winner);

        $this->statsCache = [];
    }

    /**
     * @param  Collection<int, GamePlayer>  $gamePlayers
     */
    private function updatePlayerStats(Collection $gamePlayers, ?User $winner): void
    {
        foreach ($gamePlayers as $gamePlayer) {
            $user = $gamePlayer->user;
            $stats = $this->getStats($user);

            $this->updateGameScore($stats, $gamePlayer->score);
            $this->updateWinLossDraw($stats, $user, $winner);
            $this->updateWinStreak($stats, $user, $winner);

            $stats->save();
        }
    }

    private function getStats(User $user): UserStatistics
    {
        if (! isset($this->statsCache[$user->id])) {
            $this->statsCache[$user->id] = UserStatistics::firstOrCreate(['user_id' => $user->id]);
        }

        return $this->statsCache[$user->id];
    }

    private function updateGameScore(UserStatistics $stats, int $score): void
    {
        $stats->total_game_score += $score;

        if ($score > $stats->highest_game_score) {
            $stats->highest_game_score = $score;
        }
    }

    private function updateWinLossDraw(UserStatistics $stats, User $user, ?User $winner): void
    {
        if (! $winner instanceof \App\Domain\User\Models\User) {
            $stats->games_draw++;

            return;
        }

        if ($winner->id === $user->id) {
            $stats->games_won++;

            return;
        }

        $stats->games_lost++;
    }

    private function updateWinStreak(UserStatistics $stats, User $user, ?User $winner): void
    {
        if (! $winner || $winner->id !== $user->id) {
            $stats->current_win_streak = 0;

            return;
        }

        $stats->current_win_streak++;

        if ($stats->current_win_streak > $stats->best_win_streak) {
            $stats->best_win_streak = $stats->current_win_streak;
        }
    }

    /**
     * @param  Collection<int, GamePlayer>  $gamePlayers
     */
    private function updateWinnerSpecificStats(Game $game, Collection $gamePlayers, User $winner): void
    {
        $stats = $this->getStats($winner);

        $this->updateComebackStat($stats, $game, $winner);
        $this->updateClosestVictoryStat($stats, $gamePlayers, $winner);
        $this->updateFirstMoveWinStat($stats, $game, $winner);

        $stats->save();
    }

    private function updateComebackStat(UserStatistics $stats, Game $game, User $winner): void
    {
        $biggestDeficit = $this->calculateBiggestDeficit($game, $winner);

        if ($biggestDeficit > $stats->biggest_comeback) {
            $stats->biggest_comeback = $biggestDeficit;
        }
    }

    private function calculateBiggestDeficit(Game $game, User $user): int
    {
        $moves = $game->moves()->orderBy('created_at')->get();

        $userScore = 0;
        $opponentScore = 0;
        $biggestDeficit = 0;

        foreach ($moves as $move) {
            if ($move->user_id === $user->id) {
                $userScore += $move->score;
            } else {
                $opponentScore += $move->score;
            }

            $deficit = $opponentScore - $userScore;
            $biggestDeficit = max($biggestDeficit, $deficit);
        }

        return $biggestDeficit;
    }

    /**
     * @param  Collection<int, GamePlayer>  $gamePlayers
     */
    private function updateClosestVictoryStat(UserStatistics $stats, Collection $gamePlayers, User $winner): void
    {
        $winnerPlayer = $gamePlayers->firstWhere('user_id', $winner->id);
        $loserPlayer = $gamePlayers->firstWhere('user_id', '!=', $winner->id);

        if (! $winnerPlayer || ! $loserPlayer) {
            return;
        }

        $winMargin = $winnerPlayer->score - $loserPlayer->score;

        // Only track closest victory when winner actually had a higher score
        // (not when opponent resigned while winning on points)
        if ($winMargin <= 0) {
            return;
        }

        if ($stats->closest_victory === null || $winMargin < $stats->closest_victory) {
            $stats->closest_victory = $winMargin;
        }
    }

    private function updateFirstMoveWinStat(UserStatistics $stats, Game $game, User $winner): void
    {
        $firstMove = $game->moves()->orderBy('created_at')->first();

        if ($firstMove && $firstMove->user_id === $winner->id) {
            $stats->first_move_wins++;
        }
    }

    /**
     * @param  Collection<int, GamePlayer>  $gamePlayers
     */
    private function updateEloRatings(Game $game, Collection $gamePlayers, ?User $winner): void
    {
        if (! $winner instanceof \App\Domain\User\Models\User) {
            return;
        }

        $winnerPlayer = $gamePlayers->firstWhere('user_id', $winner->id);
        $loserPlayer = $gamePlayers->firstWhere('user_id', '!=', $winner->id);

        if (! $winnerPlayer || ! $loserPlayer) {
            return;
        }

        $winnerUser = $winnerPlayer->user;
        $loserUser = $loserPlayer->user;

        $result = $this->eloCalculator->calculate($winnerUser->elo_rating, $loserUser->elo_rating);

        $this->recordEloChange($winnerUser, $game, $winnerUser->elo_rating, $result->winnerNewElo);
        $this->recordEloChange($loserUser, $game, $loserUser->elo_rating, $result->loserNewElo);

        $winnerUser->update(['elo_rating' => $result->winnerNewElo]);
        $loserUser->update(['elo_rating' => $result->loserNewElo]);

        $this->updateEloExtremes($winnerUser, $result->winnerNewElo);
        $this->updateEloExtremes($loserUser, $result->loserNewElo);
    }

    private function recordEloChange(User $user, Game $game, int $eloBefore, int $eloAfter): void
    {
        EloHistory::create([
            'user_id' => $user->id,
            'game_id' => $game->id,
            'elo_before' => $eloBefore,
            'elo_after' => $eloAfter,
            'elo_change' => $eloAfter - $eloBefore,
        ]);
    }

    private function updateEloExtremes(User $user, int $newElo): void
    {
        $stats = $this->getStats($user);

        $changed = false;

        if ($newElo > $stats->highest_elo_ever) {
            $stats->highest_elo_ever = $newElo;
            $changed = true;
        }

        if ($stats->lowest_elo_ever === 0 || $newElo < $stats->lowest_elo_ever) {
            $stats->lowest_elo_ever = $newElo;
            $changed = true;
        }

        if ($changed) {
            $stats->save();
        }
    }

    /**
     * @param  Collection<int, GamePlayer>  $gamePlayers
     */
    private function updateHeadToHeadRecords(Collection $gamePlayers, ?User $winner): void
    {
        $player1 = $gamePlayers->first();
        $player2 = $gamePlayers->last();

        $h2h1 = HeadToHeadStats::firstOrCreate(
            ['user_id' => $player1->user_id, 'opponent_id' => $player2->user_id]
        );
        $h2h2 = HeadToHeadStats::firstOrCreate(
            ['user_id' => $player2->user_id, 'opponent_id' => $player1->user_id]
        );

        $h2h1->total_score_for += $player1->score;
        $h2h1->total_score_against += $player2->score;
        $h2h2->total_score_for += $player2->score;
        $h2h2->total_score_against += $player1->score;

        if (! $winner instanceof \App\Domain\User\Models\User) {
            $h2h1->draws++;
            $h2h2->draws++;
        } elseif ($winner->id === $player1->user_id) {
            $h2h1->wins++;
            $h2h2->losses++;
        } else {
            $h2h1->losses++;
            $h2h2->wins++;
        }

        $h2h1->save();
        $h2h2->save();
    }
}
