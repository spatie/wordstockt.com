<?php

namespace App\Domain\Achievement\Achievements\Streaks;

use App\Domain\Achievement\Contracts\GameEndTriggerableAchievement;
use App\Domain\Achievement\Data\AchievementContext;
use App\Domain\Achievement\Data\AchievementDefinition;
use App\Domain\Game\Models\Game;
use App\Domain\User\Models\User;

class MonthlyMasterAchievement implements GameEndTriggerableAchievement
{
    private int $targetDays = 30;

    public function id(): string
    {
        return 'monthly_master';
    }

    public function definition(): AchievementDefinition
    {
        return new AchievementDefinition(
            id: $this->id(),
            name: 'Monthly Master',
            description: 'Play on 30 different days in a single month',
            icon: '🗓️',
            category: 'streaks',
        );
    }

    public function checkGameEnd(User $user, Game $game): ?AchievementContext
    {
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        $distinctDays = $user->moves()
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->selectRaw('DATE(created_at) as play_date')
            ->distinct()
            ->count();

        if ($distinctDays < $this->targetDays) {
            return null;
        }

        return new AchievementContext([
            'days_played' => $distinctDays,
            'month' => now()->format('F Y'),
        ]);
    }
}
