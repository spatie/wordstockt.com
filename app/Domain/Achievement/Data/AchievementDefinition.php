<?php

namespace App\Domain\Achievement\Data;

class AchievementDefinition
{
    public function __construct(
        public string $id,
        public string $name,
        public string $description,
        public string $icon,
        public string $category,
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'icon' => $this->icon,
            'category' => $this->category,
        ];
    }
}
