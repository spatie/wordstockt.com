<?php

declare(strict_types=1);

namespace App\Domain\Game\Support\Scoring\Rules;

use App\Domain\Game\Support\Scoring\ScoringContext;
use App\Domain\Game\Support\Scoring\ScoringResult;

class EndGameBonusRule extends ScoringRule
{
    private int $endGameBonus = 25;

    public function apply(ScoringContext $context, ScoringResult $result): ScoringResult
    {
        if (! $context->isEndGame) {
            return $result;
        }

        if (! $context->playerClearedRack) {
            return $result;
        }

        if (! $this->isBagEmpty($context)) {
            return $result;
        }

        return $result->addBonus(
            ruleIdentifier: $this->getIdentifier(),
            points: $this->endGameBonus,
            description: 'First to clear rack when bag is empty',
        );
    }

    private function isBagEmpty(ScoringContext $context): bool
    {
        return empty($context->game->tile_bag);
    }
}
