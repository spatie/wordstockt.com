<?php

declare(strict_types=1);

namespace App\Domain\Game\Support\Rules\Turn;

use App\Domain\Game\Data\Move;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Support\Board;
use App\Domain\Game\Support\Rules\RuleResult;
use App\Domain\Support\Models\Dictionary;

class WordValidationRule extends TurnRule
{
    public function __construct(
        private readonly Board $boardService,
    ) {}

    public function validate(Game $game, Move $move, array $board): RuleResult
    {
        $boardWithTiles = $this->boardService->placeTiles($board, $move->tiles);
        $formedWords = $this->boardService->findFormedWords($boardWithTiles, $move->tiles);

        if ($formedWords === []) {
            return RuleResult::fail($this->getIdentifier(), 'No valid words formed.');
        }

        $words = collect($formedWords)->pluck('word')->all();
        $invalidWords = Dictionary::findInvalidWords($words, $game->language);

        if ($invalidWords !== []) {
            return RuleResult::fail(
                $this->getIdentifier(),
                'Invalid word(s): '.implode(', ', $invalidWords)
            );
        }

        return RuleResult::pass($this->getIdentifier());
    }
}
