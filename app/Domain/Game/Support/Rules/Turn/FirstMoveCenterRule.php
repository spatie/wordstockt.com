<?php

declare(strict_types=1);

namespace App\Domain\Game\Support\Rules\Turn;

use App\Domain\Game\Data\Move;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Support\Rules\RuleResult;

class FirstMoveCenterRule extends TurnRule
{
    public function validate(Game $game, Move $move, array $board): RuleResult
    {
        $isFirstMove = $this->isBoardEmpty($board);

        if (! $isFirstMove) {
            return RuleResult::pass($this->getIdentifier());
        }

        if (! $move->coversCenter()) {
            return RuleResult::fail(
                $this->getIdentifier(),
                'The first move must cover the center square.'
            );
        }

        return RuleResult::pass($this->getIdentifier());
    }

    private function isBoardEmpty(array $board): bool
    {
        foreach ($board as $row) {
            foreach ($row as $cell) {
                if ($cell !== null) {
                    return false;
                }
            }
        }

        return true;
    }
}
