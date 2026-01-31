<?php

declare(strict_types=1);

namespace App\Domain\Game\Support\Rules\Game;

use App\Domain\Game\Enums\GameAction;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Support\Rules\RuleResult;
use App\Domain\User\Models\User;

class TurnOrderRule extends GameRule
{
    public function isActionAllowed(Game $game, User $user, GameAction $action): RuleResult
    {
        if ($action === GameAction::Resign) {
            return RuleResult::pass($this->getIdentifier());
        }

        if ($game->current_turn_user_id !== $user->id) {
            return RuleResult::fail(
                $this->getIdentifier(),
                'It is not your turn.'
            );
        }

        return RuleResult::pass($this->getIdentifier());
    }
}
