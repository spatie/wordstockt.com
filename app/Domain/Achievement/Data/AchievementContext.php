<?php

namespace App\Domain\Achievement\Data;

class AchievementContext
{
    public function __construct(
        public array $data = [],
    ) {}

    public function toArray(): array
    {
        return $this->data;
    }

    public function toJson(): string
    {
        return json_encode($this->data);
    }
}
