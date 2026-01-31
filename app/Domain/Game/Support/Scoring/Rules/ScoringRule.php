<?php

declare(strict_types=1);

namespace App\Domain\Game\Support\Scoring\Rules;

use App\Domain\Game\Support\Scoring\ScoringContext;
use App\Domain\Game\Support\Scoring\ScoringResult;
use Illuminate\Support\Str;

abstract class ScoringRule
{
    public function getIdentifier(): string
    {
        return 'scoring.'.$this->getIdentifierName();
    }

    public function getName(): string
    {
        return Str::headline($this->getIdentifierName());
    }

    public function isEnabled(): bool
    {
        return true;
    }

    abstract public function apply(ScoringContext $context, ScoringResult $result): ScoringResult;

    private function getIdentifierName(): string
    {
        return Str::of(class_basename($this))
            ->chopEnd('Rule')
            ->snake()
            ->toString();
    }
}
