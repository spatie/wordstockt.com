<?php

namespace App\Domain\Game\Enums;

enum BoardType: string
{
    case Standard = 'standard';
    case NoBonuses = 'no_bonuses';
    case Custom = 'custom';
}
