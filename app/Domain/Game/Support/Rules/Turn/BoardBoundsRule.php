<?php

declare(strict_types=1);

namespace App\Domain\Game\Support\Rules\Turn;

use App\Domain\Game\Data\Move;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Support\Board;
use App\Domain\Game\Support\Rules\RuleResult;

class BoardBoundsRule extends TurnRule
{
    public function validate(Game $game, Move $move, array $board): RuleResult
    {
        $boardService = app(Board::class);

        foreach ($move->tiles as $tile) {
            if (! $boardService->isWithinBounds($tile['x'], $tile['y'])) {
                return RuleResult::fail(
                    $this->getIdentifier(),
                    'Tile position is out of bounds.'
                );
            }
        }

        return RuleResult::pass($this->getIdentifier());
    }
}
