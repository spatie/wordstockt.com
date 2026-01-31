<?php

namespace App\Domain\Achievement\Contracts;

use App\Domain\Achievement\Data\AchievementDefinition;

interface Achievement
{
    public function id(): string;

    public function definition(): AchievementDefinition;
}
