<?php

namespace App\Domain\Game\Enums;

enum SquareType: string
{
    case TripleWord = '3W';
    case DoubleWord = '2W';
    case TripleLetter = '3L';
    case DoubleLetter = '2L';
    case Star = 'STAR';

    public function letterMultiplier(): int
    {
        return match ($this) {
            self::DoubleLetter => 2,
            self::TripleLetter => 3,
            default => 1,
        };
    }

    public function wordMultiplier(): int
    {
        return match ($this) {
            self::DoubleWord => 2,
            self::TripleWord => 3,
            default => 1,
        };
    }
}
