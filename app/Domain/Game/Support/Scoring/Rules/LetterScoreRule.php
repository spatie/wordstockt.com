<?php

declare(strict_types=1);

namespace App\Domain\Game\Support\Scoring\Rules;

use App\Domain\Game\Support\Scoring\ScoringContext;
use App\Domain\Game\Support\Scoring\ScoringResult;

class LetterScoreRule extends ScoringRule
{
    public function apply(ScoringContext $context, ScoringResult $result): ScoringResult
    {
        collect($context->words)->each(
            fn (array $wordData) => $this->scoreWord($context, $result, $wordData)
        );

        return $result;
    }

    private function scoreWord(ScoringContext $context, ScoringResult $result, array $wordData): void
    {
        $tileScores = collect($wordData['tiles'])
            ->map(fn (array $tile): array => $this->calculateTileScore($context, $tile));

        $baseScore = $tileScores->sum('letterScore');

        $wordMultiplier = $tileScores
            ->pluck('wordMultiplier')
            ->filter()
            ->reduce(fn (int $carry, int $value): int => $carry * $value, 1);

        $multipliers = $tileScores->pluck('multipliers')->flatten(1)->all();

        $result->addWordScore(
            word: $wordData['word'],
            baseScore: $baseScore,
            multipliedScore: $baseScore * $wordMultiplier,
            multipliers: $multipliers,
        );
    }

    private function calculateTileScore(ScoringContext $context, array $tile): array
    {
        $letterPoints = $tile['is_blank'] ? 0 : $tile['points'];
        $position = [$tile['x'], $tile['y']];

        if (! $context->isPositionNewlyPlaced($tile['x'], $tile['y'])) {
            return $this->tileResult($letterPoints);
        }

        $squareType = $context->board->getSquareType($tile['x'], $tile['y'], $context->game->board_template);

        if (! $squareType instanceof \App\Domain\Game\Enums\SquareType) {
            return $this->tileResult($letterPoints);
        }

        $letterMultiplierValue = $squareType->letterMultiplier();
        $wordMultiplierValue = $squareType->wordMultiplier();

        $multipliers = collect()
            ->when($letterMultiplierValue > 1, fn ($c) => $c->push([
                'type' => 'letter',
                'value' => $letterMultiplierValue,
                'position' => $position,
            ]))
            ->when($wordMultiplierValue > 1, fn ($c) => $c->push([
                'type' => 'word',
                'value' => $wordMultiplierValue,
                'position' => $position,
            ]))
            ->all();

        return [
            'letterScore' => $letterPoints * $letterMultiplierValue,
            'wordMultiplier' => $wordMultiplierValue > 1 ? $wordMultiplierValue : null,
            'multipliers' => $multipliers,
        ];
    }

    private function tileResult(int $letterScore): array
    {
        return [
            'letterScore' => $letterScore,
            'wordMultiplier' => null,
            'multipliers' => [],
        ];
    }
}
