<?php

namespace App\Domain\Achievement\Data;

use App\Domain\Achievement\Contracts\Achievement;

class UnlockedAchievement
{
    public function __construct(
        public Achievement $achievement,
        public AchievementContext $context,
    ) {}

    public function toArray(): array
    {
        return [
            ...$this->achievement->definition()->toArray(),
            'context' => $this->context->toArray(),
        ];
    }
}
