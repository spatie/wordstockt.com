<?php

namespace App\Domain\Achievement\Achievements\Streaks;

use App\Domain\Achievement\Contracts\GameEndTriggerableAchievement;
use App\Domain\Achievement\Data\AchievementContext;
use App\Domain\Achievement\Data\AchievementDefinition;
use App\Domain\Game\Models\Game;
use App\Domain\User\Models\User;

class DailySevenAchievement implements GameEndTriggerableAchievement
{
    private int $targetDays = 7;

    public function id(): string
    {
        return 'daily_seven';
    }

    public function definition(): AchievementDefinition
    {
        return new AchievementDefinition(
            id: $this->id(),
            name: 'Weekly Warrior',
            description: 'Play every day for 7 consecutive days',
            icon: '📅',
            category: 'streaks',
        );
    }

    public function checkGameEnd(User $user, Game $game): ?AchievementContext
    {
        $distinctDays = $user->moves()
            ->where('created_at', '>=', now()->subDays($this->targetDays))
            ->selectRaw('DATE(created_at) as play_date')
            ->distinct()
            ->count();

        if ($distinctDays < $this->targetDays) {
            return null;
        }

        return new AchievementContext(['consecutive_days' => $this->targetDays]);
    }
}
