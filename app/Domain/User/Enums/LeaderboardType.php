<?php

namespace App\Domain\User\Enums;

enum LeaderboardType: string
{
    case Elo = 'elo';
    case Monthly = 'monthly';
    case Yearly = 'yearly';

    public function days(): ?int
    {
        return match ($this) {
            self::Elo => null,
            self::Monthly => 30,
            self::Yearly => 365,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Elo => 'ELO Rating',
            self::Monthly => 'Monthly Wins',
            self::Yearly => 'Yearly Wins',
        };
    }
}
