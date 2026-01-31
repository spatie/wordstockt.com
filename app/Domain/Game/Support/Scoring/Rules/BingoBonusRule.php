<?php

declare(strict_types=1);

namespace App\Domain\Game\Support\Scoring\Rules;

use App\Domain\Game\Support\Scoring\ScoringContext;
use App\Domain\Game\Support\Scoring\ScoringResult;

class BingoBonusRule extends ScoringRule
{
    private int $bingoBonus = 50;

    private int $tilesForBingo = 7;

    public function apply(ScoringContext $context, ScoringResult $result): ScoringResult
    {
        if ($context->tileCount() < $this->tilesForBingo) {
            return $result;
        }

        return $result->addBonus(
            ruleIdentifier: $this->getIdentifier(),
            points: $this->bingoBonus,
            description: 'Used all 7 tiles',
        );
    }
}
