<?php

namespace App\Domain\Game\Support\TileDistributions;

class EnglishDistribution extends TileDistribution
{
    protected function distribution(): array
    {
        return [
            'A' => ['count' => 9, 'points' => 1],
            'B' => ['count' => 2, 'points' => 3],
            'C' => ['count' => 2, 'points' => 3],
            'D' => ['count' => 4, 'points' => 2],
            'E' => ['count' => 12, 'points' => 1],
            'F' => ['count' => 2, 'points' => 4],
            'G' => ['count' => 3, 'points' => 2],
            'H' => ['count' => 2, 'points' => 4],
            'I' => ['count' => 9, 'points' => 1],
            'J' => ['count' => 1, 'points' => 8],
            'K' => ['count' => 1, 'points' => 5],
            'L' => ['count' => 4, 'points' => 1],
            'M' => ['count' => 2, 'points' => 3],
            'N' => ['count' => 6, 'points' => 1],
            'O' => ['count' => 8, 'points' => 1],
            'P' => ['count' => 2, 'points' => 3],
            'Q' => ['count' => 1, 'points' => 10],
            'R' => ['count' => 6, 'points' => 1],
            'S' => ['count' => 4, 'points' => 1],
            'T' => ['count' => 6, 'points' => 1],
            'U' => ['count' => 4, 'points' => 1],
            'V' => ['count' => 2, 'points' => 4],
            'W' => ['count' => 2, 'points' => 4],
            'X' => ['count' => 1, 'points' => 8],
            'Y' => ['count' => 2, 'points' => 4],
            'Z' => ['count' => 1, 'points' => 10],
            '*' => ['count' => 2, 'points' => 0],
        ];
    }
}
