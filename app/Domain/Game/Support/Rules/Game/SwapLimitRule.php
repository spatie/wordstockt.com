<?php

declare(strict_types=1);

namespace App\Domain\Game\Support\Rules\Game;

use App\Domain\Game\Enums\GameAction;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Support\Rules\RuleResult;
use App\Domain\User\Models\User;

class SwapLimitRule extends GameRule
{
    private const int MIN_TILES_FOR_SWAP = 7;

    public function isActionAllowed(Game $game, User $user, GameAction $action): RuleResult
    {
        if ($action !== GameAction::Swap) {
            return RuleResult::pass($this->getIdentifier());
        }

        $tilesInBag = count($game->tile_bag ?? []);

        if ($tilesInBag < self::MIN_TILES_FOR_SWAP) {
            return RuleResult::fail(
                $this->getIdentifier(),
                'Not enough tiles in the bag to swap. Need at least '.self::MIN_TILES_FOR_SWAP.' tiles.'
            );
        }

        return RuleResult::pass($this->getIdentifier());
    }
}
