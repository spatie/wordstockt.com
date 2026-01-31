<?php

declare(strict_types=1);

namespace App\Domain\User\Support\EloCalculator;

class EloCalculator
{
    private readonly int $kFactor;

    private readonly int $scaleFactor;

    public function __construct(?int $kFactor = null, ?int $scaleFactor = null)
    {
        $this->kFactor = $kFactor ?? config('game.elo.k_factor', 32);
        $this->scaleFactor = $scaleFactor ?? config('game.elo.scale_factor', 400);
    }

    public function calculate(int $winnerElo, int $loserElo): EloResult
    {
        $expectedWinner = $this->expectedScore($winnerElo, $loserElo);
        $expectedLoser = $this->expectedScore($loserElo, $winnerElo);

        $newWinnerElo = $this->newRating($winnerElo, 1, $expectedWinner);
        $newLoserElo = $this->newRating($loserElo, 0, $expectedLoser);

        return new EloResult(
            winnerNewElo: $newWinnerElo,
            winnerChange: $newWinnerElo - $winnerElo,
            loserNewElo: $newLoserElo,
            loserChange: $newLoserElo - $loserElo,
        );
    }

    private function expectedScore(int $playerElo, int $opponentElo): float
    {
        return 1 / (1 + 10 ** (($opponentElo - $playerElo) / $this->scaleFactor));
    }

    private function newRating(int $currentElo, int $actualScore, float $expectedScore): int
    {
        return (int) round($currentElo + $this->kFactor * ($actualScore - $expectedScore));
    }
}
