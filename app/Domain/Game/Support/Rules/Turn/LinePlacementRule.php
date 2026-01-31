<?php

declare(strict_types=1);

namespace App\Domain\Game\Support\Rules\Turn;

use App\Domain\Game\Data\Move;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Support\Rules\RuleResult;

class LinePlacementRule extends TurnRule
{
    public function validate(Game $game, Move $move, array $board): RuleResult
    {
        if ($move->tileCount() <= 1) {
            return RuleResult::pass($this->getIdentifier());
        }

        if ($move->isHorizontal()) {
            return RuleResult::pass($this->getIdentifier());
        }

        if ($move->isVertical()) {
            return RuleResult::pass($this->getIdentifier());
        }

        return RuleResult::fail(
            $this->getIdentifier(),
            'Tiles must be placed in a single row or column.'
        );
    }
}
