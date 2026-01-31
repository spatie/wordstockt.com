<?php

declare(strict_types=1);

namespace App\Domain\User\Support\EloCalculator;

readonly class EloResult
{
    public function __construct(
        public int $winnerNewElo,
        public int $winnerChange,
        public int $loserNewElo,
        public int $loserChange,
    ) {}
}
