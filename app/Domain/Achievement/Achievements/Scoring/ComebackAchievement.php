<?php

namespace App\Domain\Achievement\Achievements\Scoring;

use App\Domain\Achievement\Contracts\GameEndTriggerableAchievement;
use App\Domain\Achievement\Data\AchievementContext;
use App\Domain\Achievement\Data\AchievementDefinition;
use App\Domain\Game\Models\Game;
use App\Domain\User\Models\User;

class ComebackAchievement implements GameEndTriggerableAchievement
{
    private int $minDeficit = 50;

    public function id(): string
    {
        return 'comeback';
    }

    public function definition(): AchievementDefinition
    {
        return new AchievementDefinition(
            id: $this->id(),
            name: 'Comeback King',
            description: 'Win after being 50 or more points behind',
            icon: '🔄',
            category: 'scoring',
        );
    }

    public function checkGameEnd(User $user, Game $game): ?AchievementContext
    {
        if ($game->winner_id !== $user->id) {
            return null;
        }

        $stats = $user->statistics;

        if (! $stats || $stats->biggest_comeback < $this->minDeficit) {
            return null;
        }

        return new AchievementContext(['deficit_overcome' => $stats->biggest_comeback]);
    }
}
