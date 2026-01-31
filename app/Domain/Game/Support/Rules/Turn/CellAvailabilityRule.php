<?php

declare(strict_types=1);

namespace App\Domain\Game\Support\Rules\Turn;

use App\Domain\Game\Data\Move;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Support\Rules\RuleResult;

class CellAvailabilityRule extends TurnRule
{
    public function validate(Game $game, Move $move, array $board): RuleResult
    {
        foreach ($move->tiles as $tile) {
            if ($board[$tile['y']][$tile['x']] !== null) {
                return RuleResult::fail(
                    $this->getIdentifier(),
                    'Position is already occupied by another tile.'
                );
            }
        }

        return RuleResult::pass($this->getIdentifier());
    }
}
