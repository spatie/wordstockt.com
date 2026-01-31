<?php

declare(strict_types=1);

namespace App\Domain\Game\Support\Scoring\Rules;

use App\Domain\Game\Support\Scoring\ScoringContext;
use App\Domain\Game\Support\Scoring\ScoringResult;

class WordLengthBonusRule extends ScoringRule
{
    /**
     * Bonus points based on number of tiles played in the turn.
     */
    private array $tilesPlayedBonuses = [
        2 => 3,
        3 => 6,
        4 => 12,
        5 => 25,
        6 => 50,
        7 => 100,
    ];

    /**
     * Bonus points for extending an existing word by at least 2 letters.
     * Key is the original word length before extension.
     */
    private array $extensionBonuses = [
        2 => 10,
        3 => 12,
        4 => 15,
        5 => 19,
        6 => 23,
        7 => 28,
        8 => 35,
        9 => 43,
        10 => 53,
        11 => 66,
        12 => 81,
        13 => 100,
    ];

    public function apply(ScoringContext $context, ScoringResult $result): ScoringResult
    {
        $this->applyTilesPlayedBonus($context, $result);
        $this->applyWordExtensionBonus($context, $result);

        return $result;
    }

    private function applyTilesPlayedBonus(ScoringContext $context, ScoringResult $result): void
    {
        $tilesPlayed = $context->tileCount();
        $bonus = $this->tilesPlayedBonuses[$tilesPlayed] ?? 0;

        if ($bonus === 0) {
            return;
        }

        $result->addBonus(
            ruleIdentifier: $this->getIdentifier(),
            points: $bonus,
            description: "Played {$tilesPlayed} tiles",
        );
    }

    private function applyWordExtensionBonus(ScoringContext $context, ScoringResult $result): void
    {
        $bestExtensionBonus = 0;
        $bestOriginalLength = 0;
        $bestWord = '';

        foreach ($context->words as $wordData) {
            $extensionInfo = $this->analyzeWordExtension($wordData, $context);

            if ($extensionInfo === null) {
                continue;
            }

            $bonus = $this->getExtensionBonus($extensionInfo['originalLength']);

            if ($bonus > $bestExtensionBonus) {
                $bestExtensionBonus = $bonus;
                $bestOriginalLength = $extensionInfo['originalLength'];
                $bestWord = $wordData['word'];
            }
        }

        if ($bestExtensionBonus === 0) {
            return;
        }

        $result->addBonus(
            ruleIdentifier: $this->getIdentifier().'.extension',
            points: $bestExtensionBonus,
            description: "Extended {$bestOriginalLength}-letter word ({$bestWord})",
        );
    }

    /**
     * Analyze a word to check if it's an extension of an existing word.
     *
     * @return array{originalLength: int, extensionLength: int}|null
     */
    private function analyzeWordExtension(array $wordData, ScoringContext $context): ?array
    {
        $existingTileCount = 0;
        $newTileCount = 0;

        foreach ($wordData['tiles'] as $tile) {
            if ($context->isPositionNewlyPlaced($tile['x'], $tile['y'])) {
                $newTileCount++;
            } else {
                $existingTileCount++;
            }
        }

        // Must have at least 2 existing tiles (original word)
        // and at least 2 new tiles (extension)
        if ($existingTileCount < 2 || $newTileCount < 2) {
            return null;
        }

        return [
            'originalLength' => $existingTileCount,
            'extensionLength' => $newTileCount,
        ];
    }

    private function getExtensionBonus(int $originalLength): int
    {
        if ($originalLength >= 13) {
            return $this->extensionBonuses[13];
        }

        return $this->extensionBonuses[$originalLength] ?? 0;
    }
}
