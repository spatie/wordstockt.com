<?php

declare(strict_types=1);

namespace App\Domain\Game\Support\Rules\Turn;

use App\Domain\Game\Data\Move;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Support\Rules\RuleResult;
use Illuminate\Support\Str;

abstract class TurnRule
{
    public function getIdentifier(): string
    {
        return 'turn.'.$this->getIdentifierName();
    }

    public function getName(): string
    {
        return Str::headline($this->getIdentifierName());
    }

    public function isEnabled(): bool
    {
        return true;
    }

    abstract public function validate(Game $game, Move $move, array $board): RuleResult;

    private function getIdentifierName(): string
    {
        return Str::of(class_basename($this))
            ->chopEnd('Rule')
            ->snake()
            ->toString();
    }
}
