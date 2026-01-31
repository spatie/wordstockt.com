<?php

declare(strict_types=1);

namespace App\Domain\Game\Support\Rules\Game;

use App\Domain\Game\Enums\GameAction;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Support\Rules\RuleResult;
use App\Domain\User\Models\User;
use Illuminate\Support\Str;

abstract class GameRule
{
    public function getIdentifier(): string
    {
        return 'game.'.$this->getIdentifierName();
    }

    public function getName(): string
    {
        return Str::headline($this->getIdentifierName());
    }

    public function isEnabled(): bool
    {
        return true;
    }

    abstract public function isActionAllowed(Game $game, User $user, GameAction $action): RuleResult;

    private function getIdentifierName(): string
    {
        return Str::of(class_basename($this))
            ->chopEnd('Rule')
            ->snake()
            ->toString();
    }
}
