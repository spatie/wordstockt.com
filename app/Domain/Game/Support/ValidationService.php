<?php

declare(strict_types=1);

namespace App\Domain\Game\Support;

use App\Domain\Game\Data\Move;
use App\Domain\Game\Data\PlacementValidationResult;
use App\Domain\Game\Data\TileStatus;
use App\Domain\Game\Data\ValidatedWord;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Support\Rules\RuleEngine;
use App\Domain\Game\Support\Scoring\ScoringEngine;
use App\Domain\Support\Models\Dictionary;
use Illuminate\Support\Collection;

readonly class ValidationService
{
    private const array SKIP_RULES = [
        'turn.word_validation',
        'turn.tiles_in_rack',
    ];

    public function __construct(
        private Board $boardService,
        private RuleEngine $ruleEngine,
        private ScoringEngine $scoringEngine,
    ) {}

    public function validatePlacement(Game $game, Move $move): PlacementValidationResult
    {
        $placementErrors = $this->validatePlacementRules($game, $move, $game->board_state);

        if ($placementErrors->isNotEmpty()) {
            return $this->buildPlacementErrorResult($move, $placementErrors);
        }

        $boardWithTiles = $this->boardService->placeTiles($game->board_state, $move->tiles);
        $formedWords = $this->boardService->findFormedWords($boardWithTiles, $move->tiles);
        $validatedWords = $this->validateWords($formedWords, $game->language);
        $tileStatus = $this->calculateTileStatus($move, $validatedWords);

        $potentialScore = null;
        $allWordsValid = $validatedWords->every(fn (ValidatedWord $word): bool => $word->valid);

        if ($allWordsValid) {
            $scoringResult = $this->scoringEngine->calculateMoveScore(
                $game,
                $formedWords,
                $move->tiles,
                $this->boardService
            );
            $potentialScore = $scoringResult->getTotal();
        }

        return new PlacementValidationResult(
            placementValid: true,
            placementErrors: collect(),
            words: $validatedWords,
            tileStatus: $tileStatus,
            potentialScore: $potentialScore,
        );
    }

    /**
     * @return Collection<int, string>
     */
    private function validatePlacementRules(Game $game, Move $move, array $board): Collection
    {
        return $this->ruleEngine->getTurnRules()
            ->reject(fn ($rule): bool => in_array($rule->getIdentifier(), self::SKIP_RULES, true))
            ->filter(fn ($rule): bool => $rule->isEnabled())
            ->map(fn ($rule): \App\Domain\Game\Support\Rules\RuleResult => $rule->validate($game, $move, $board))
            ->filter(fn ($result): bool => $result->failed())
            ->map(fn ($result): string => $result->message)
            ->values();
    }

    /**
     * @param  Collection<int, string>  $errors
     */
    private function buildPlacementErrorResult(Move $move, Collection $errors): PlacementValidationResult
    {
        $tileStatus = collect($move->tiles)
            ->map(fn (array $tile): \App\Domain\Game\Data\TileStatus => new TileStatus($tile['x'], $tile['y'], valid: false));

        return new PlacementValidationResult(
            placementValid: false,
            placementErrors: $errors,
            words: collect(),
            tileStatus: $tileStatus,
        );
    }

    /**
     * @return Collection<int, ValidatedWord>
     */
    private function validateWords(array $formedWords, string $language): Collection
    {
        return collect($formedWords)
            ->map(fn (array $wordData): \App\Domain\Game\Data\ValidatedWord => new ValidatedWord(
                word: $wordData['word'],
                valid: Dictionary::isValidWord($wordData['word'], $language),
                tiles: collect($wordData['tiles'])
                    ->map(fn (array $tile): array => ['x' => $tile['x'], 'y' => $tile['y']]),
            ));
    }

    /**
     * @param  Collection<int, ValidatedWord>  $validatedWords
     * @return Collection<int, TileStatus>
     */
    private function calculateTileStatus(Move $move, Collection $validatedWords): Collection
    {
        return collect($move->tiles)
            ->map(fn (array $tile): \App\Domain\Game\Data\TileStatus => new TileStatus(
                x: $tile['x'],
                y: $tile['y'],
                valid: $this->isTileValid($tile['x'], $tile['y'], $validatedWords),
            ));
    }

    /**
     * @param  Collection<int, ValidatedWord>  $validatedWords
     */
    private function isTileValid(int $x, int $y, Collection $validatedWords): bool
    {
        $wordsContainingTile = $validatedWords->filter(
            fn (ValidatedWord $word): bool => $word->containsTileAt($x, $y)
        );

        if ($wordsContainingTile->isEmpty()) {
            return false;
        }

        return $wordsContainingTile->every(fn (ValidatedWord $word): bool => $word->valid);
    }
}
