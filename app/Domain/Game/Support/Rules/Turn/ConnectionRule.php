<?php

declare(strict_types=1);

namespace App\Domain\Game\Support\Rules\Turn;

use App\Domain\Game\Data\Move;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Support\Board;
use App\Domain\Game\Support\Rules\RuleResult;

class ConnectionRule extends TurnRule
{
    public function validate(Game $game, Move $move, array $board): RuleResult
    {
        $isFirstMove = $this->isBoardEmpty($board);

        if ($isFirstMove) {
            return RuleResult::pass($this->getIdentifier());
        }

        if (! $this->connectsToExisting($board, $move->tiles)) {
            return RuleResult::fail(
                $this->getIdentifier(),
                'Tiles must connect to existing tiles on the board.'
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

    private function connectsToExisting(array $board, array $tiles): bool
    {
        $boardService = app(Board::class);

        return array_any($tiles, fn ($tile): bool => $this->hasAdjacentTile($board, $tile['x'], $tile['y'], $boardService));
    }

    private function hasAdjacentTile(array $board, int $x, int $y, Board $boardService): bool
    {
        $adjacentPositions = [
            [$x - 1, $y],
            [$x + 1, $y],
            [$x, $y - 1],
            [$x, $y + 1],
        ];

        foreach ($adjacentPositions as [$ax, $ay]) {
            if (! $boardService->isWithinBounds($ax, $ay)) {
                continue;
            }

            if ($board[$ay][$ax] !== null) {
                return true;
            }
        }

        return false;
    }
}
