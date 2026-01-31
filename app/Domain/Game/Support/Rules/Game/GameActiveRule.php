<?php

declare(strict_types=1);

namespace App\Domain\Game\Support\Rules\Game;

use App\Domain\Game\Enums\GameAction;
use App\Domain\Game\Enums\GameStatus;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Support\Rules\RuleResult;
use App\Domain\User\Models\User;

class GameActiveRule extends GameRule
{
    public function isActionAllowed(Game $game, User $user, GameAction $action): RuleResult
    {
        if ($game->status !== GameStatus::Active) {
            return RuleResult::fail(
                $this->getIdentifier(),
                'Game is not active.'
            );
        }

        return RuleResult::pass($this->getIdentifier());
    }
}
