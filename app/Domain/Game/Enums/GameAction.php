<?php

declare(strict_types=1);

namespace App\Domain\Game\Enums;

enum GameAction: string
{
    case Play = 'play';
    case Pass = 'pass';
    case Swap = 'swap';
    case Resign = 'resign';
}
