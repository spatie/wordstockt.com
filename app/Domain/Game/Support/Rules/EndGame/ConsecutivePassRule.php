<?php

declare(strict_types=1);

namespace App\Domain\Game\Support\Rules\EndGame;

use App\Domain\Game\Enums\MoveType;
use App\Domain\Game\Models\Game;

class ConsecutivePassRule extends EndGameRule
{
    private int $maxConsecutivePasses = 4;

    public function shouldEndGame(Game $game): bool
    {
        $recentMoves = $game->moves()
            ->latest()
            ->take($this->maxConsecutivePasses)
            ->get();

        if ($recentMoves->count() < $this->maxConsecutivePasses) {
            return false;
        }

        return $recentMoves->every(
            fn ($move): bool => $move->type === MoveType::Pass
        );
    }

    public function getEndReason(): string
    {
        return 'Four consecutive passes occurred.';
    }
}
