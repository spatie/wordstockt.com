<?php

namespace App\Domain\Support\Enums;

enum WordRecommendation: string
{
    case Add = 'add';
    case Reject = 'reject';
    case Uncertain = 'uncertain';

    public function label(): string
    {
        return match ($this) {
            self::Add => 'AI recommends: ADD THIS WORD',
            self::Reject => 'AI recommends: REJECT THIS WORD',
            self::Uncertain => 'AI is unsure: CHECK MANUALLY',
        };
    }

    public function accentColor(): string
    {
        return match ($this) {
            self::Add => '#16A34A',
            self::Reject => '#DC2626',
            self::Uncertain => '#D97706',
        };
    }

    public function backgroundColor(): string
    {
        return match ($this) {
            self::Add => '#F0FDF4',
            self::Reject => '#FEF2F2',
            self::Uncertain => '#FFFBEB',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Add => '✓',
            self::Reject => '✕',
            self::Uncertain => '?',
        };
    }
}
