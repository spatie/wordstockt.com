<?php

namespace App\Domain\Game\Enums;

enum GameStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Finished = 'finished';
}
