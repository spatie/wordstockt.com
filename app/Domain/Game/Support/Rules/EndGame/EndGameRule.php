<?php

declare(strict_types=1);

namespace App\Domain\Game\Support\Rules\EndGame;

use App\Domain\Game\Models\Game;
use Illuminate\Support\Str;

abstract class EndGameRule
{
    public function getIdentifier(): string
    {
        return "endgame.{$this->getIdentifierName()}";
    }

    public function getName(): string
    {
        return Str::headline($this->getIdentifierName());
    }

    public function isEnabled(): bool
    {
        return true;
    }

    abstract public function shouldEndGame(Game $game): bool;

    abstract public function getEndReason(): string;

    private function getIdentifierName(): string
    {
        return Str::of(class_basename($this))
            ->chopEnd('Rule')
            ->snake()
            ->toString();
    }
}
