<?php

namespace App\Domain\Game\Data;

readonly class BoardTemplateValidationResult
{
    public function __construct(
        public bool $isValid,
        public array $errors = [],
    ) {}
}
