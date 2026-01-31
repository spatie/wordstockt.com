<?php

declare(strict_types=1);

namespace App\Domain\Game\Support\Rules;

readonly class RuleResult
{
    public function __construct(
        public bool $passed,
        public string $message,
        public string $ruleIdentifier,
    ) {}

    public static function pass(string $ruleIdentifier): self
    {
        return new self(
            passed: true,
            message: '',
            ruleIdentifier: $ruleIdentifier,
        );
    }

    public static function fail(string $ruleIdentifier, string $message): self
    {
        return new self(
            passed: false,
            message: $message,
            ruleIdentifier: $ruleIdentifier,
        );
    }

    public function failed(): bool
    {
        return ! $this->passed;
    }
}
