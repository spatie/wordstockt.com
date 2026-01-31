<?php

namespace App\Domain\Game\Support\TileDistributions;

use App\Domain\Game\Support\Tile;

abstract class TileDistribution
{
    /** @return array<string, array{count: int, points: int}> */
    abstract protected function distribution(): array;

    /** @return array<int, Tile> */
    public function tiles(): array
    {
        return collect($this->distribution())
            ->reject(fn (array $info, string $letter): bool => $letter === '*')
            ->flatMap(fn (array $info, string $letter) => collect()
                ->range(1, $info['count'])
                ->map(fn (): \App\Domain\Game\Support\Tile => new Tile(
                    letter: $letter,
                    points: $info['points'],
                    isBlank: false,
                ))
            )
            ->values()
            ->all();
    }

    public function getPointsForLetter(string $letter): int
    {
        return $this->distribution()[$letter]['points'] ?? 0;
    }
}
