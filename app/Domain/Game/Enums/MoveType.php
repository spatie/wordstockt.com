<?php

namespace App\Domain\Game\Enums;

enum MoveType: string
{
    case Play = 'play';
    case Pass = 'pass';
    case Swap = 'swap';
    case Resign = 'resign';
}
