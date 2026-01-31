<?php

declare(strict_types=1);

namespace App\Domain\Game\Support\Rules\Turn;

use App\Domain\Game\Data\Move;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Support\Rules\RuleResult;

class NoGapsRule extends TurnRule
{
    public function validate(Game $game, Move $move, array $board): RuleResult
    {
        if ($move->tileCount() <= 1) {
            return RuleResult::pass($this->getIdentifier());
        }

        if ($this->hasGaps($move, $board)) {
            return RuleResult::fail(
                $this->getIdentifier(),
                'There are gaps between the placed tiles.'
            );
        }

        return RuleResult::pass($this->getIdentifier());
    }

    private function hasGaps(Move $move, array $board): bool
    {
        $tiles = $move->tiles;

        if ($move->isHorizontal()) {
            return $this->hasHorizontalGaps($tiles, $board);
        }

        return $this->hasVerticalGaps($tiles, $board);
    }

    private function hasHorizontalGaps(array $tiles, array $board): bool
    {
        usort($tiles, fn (array $a, array $b): int => $a['x'] <=> $b['x']);
        $y = $tiles[0]['y'];
        $start = $tiles[0]['x'];
        $end = $tiles[count($tiles) - 1]['x'];

        for ($x = $start; $x <= $end; $x++) {
            if ($this->isGapAt($tiles, $board, $x, $y, 'x')) {
                return true;
            }
        }

        return false;
    }

    private function hasVerticalGaps(array $tiles, array $board): bool
    {
        usort($tiles, fn (array $a, array $b): int => $a['y'] <=> $b['y']);
        $x = $tiles[0]['x'];
        $start = $tiles[0]['y'];
        $end = $tiles[count($tiles) - 1]['y'];

        for ($y = $start; $y <= $end; $y++) {
            if ($this->isGapAt($tiles, $board, $x, $y, 'y')) {
                return true;
            }
        }

        return false;
    }

    private function isGapAt(array $tiles, array $board, int $x, int $y, string $axis): bool
    {
        $position = $axis === 'x' ? $x : $y;
        $hasNewTile = collect($tiles)->contains(fn ($tile): bool => $tile[$axis] === $position);

        if ($hasNewTile) {
            return false;
        }

        return $board[$y][$x] === null;
    }
}
