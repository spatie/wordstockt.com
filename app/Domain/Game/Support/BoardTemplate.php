<?php

namespace App\Domain\Game\Support;

use App\Domain\Game\Enums\BoardType;

class BoardTemplate
{
    public const MULTIPLIER_LIMITS = [
        '2L' => 28,
        '3L' => 20,
        '2W' => 20,
        '3W' => 12,
    ];

    public static function standard(): array
    {
        return app(Board::class)->getBoardTemplate();
    }

    public static function noBonuses(): array
    {
        return collect(range(0, Board::BOARD_SIZE - 1))
            ->mapWithKeys(fn ($y): array => [
                $y => collect(range(0, Board::BOARD_SIZE - 1))
                    ->map(fn ($x): ?string => $x === Board::CENTER && $y === Board::CENTER ? 'STAR' : null)
                    ->all(),
            ])
            ->all();
    }

    public static function fromType(BoardType $type, ?array $customTemplate = null): array
    {
        return match ($type) {
            BoardType::Standard => self::standard(),
            BoardType::NoBonuses => self::noBonuses(),
            BoardType::Custom => $customTemplate ?? self::standard(),
        };
    }
}
