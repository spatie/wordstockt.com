<?php

declare(strict_types=1);

namespace App\Domain\User\Support\EloCalculator;

class MultiplayerEloCalculator
{
    private readonly int $kFactor;

    private readonly int $scaleFactor;

    public function __construct(?int $kFactor = null, ?int $scaleFactor = null)
    {
        $this->kFactor = $kFactor ?? config('game.elo.k_factor', 32);
        $this->scaleFactor = $scaleFactor ?? config('game.elo.scale_factor', 400);
    }

    /**
     * @param  array<int, array{elo: int, score: float}>  $matchups
     */
    public function netChange(int $playerElo, array $matchups): int
    {
        if ($matchups === []) {
            return 0;
        }

        $total = 0.0;

        foreach ($matchups as $matchup) {
            $expected = 1 / (1 + 10 ** (($matchup['elo'] - $playerElo) / $this->scaleFactor));
            $total += $this->kFactor * ($matchup['score'] - $expected);
        }

        return (int) round($total / count($matchups));
    }
}
