<?php

namespace App\Domain\Game\Support\TileDistributions;

class DutchDistribution extends TileDistribution
{
    protected function distribution(): array
    {
        return [
            'A' => ['count' => 6, 'points' => 1],
            'B' => ['count' => 2, 'points' => 3],
            'C' => ['count' => 2, 'points' => 5],
            'D' => ['count' => 5, 'points' => 2],
            'E' => ['count' => 18, 'points' => 1],
            'F' => ['count' => 2, 'points' => 4],
            'G' => ['count' => 3, 'points' => 3],
            'H' => ['count' => 2, 'points' => 4],
            'I' => ['count' => 4, 'points' => 1],
            'J' => ['count' => 2, 'points' => 4],
            'K' => ['count' => 3, 'points' => 3],
            'L' => ['count' => 3, 'points' => 3],
            'M' => ['count' => 3, 'points' => 3],
            'N' => ['count' => 10, 'points' => 1],
            'O' => ['count' => 6, 'points' => 1],
            'P' => ['count' => 2, 'points' => 3],
            'Q' => ['count' => 1, 'points' => 10],
            'R' => ['count' => 5, 'points' => 2],
            'S' => ['count' => 5, 'points' => 2],
            'T' => ['count' => 5, 'points' => 2],
            'U' => ['count' => 3, 'points' => 4],
            'V' => ['count' => 2, 'points' => 4],
            'W' => ['count' => 2, 'points' => 5],
            'X' => ['count' => 1, 'points' => 8],
            'Y' => ['count' => 1, 'points' => 8],
            'Z' => ['count' => 2, 'points' => 4],
            '*' => ['count' => 2, 'points' => 0],
        ];
    }
}
