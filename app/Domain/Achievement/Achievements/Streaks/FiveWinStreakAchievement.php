<?php

namespace App\Domain\Achievement\Achievements\Streaks;

use App\Domain\Achievement\Contracts\GameEndTriggerableAchievement;
use App\Domain\Achievement\Data\AchievementContext;
use App\Domain\Achievement\Data\AchievementDefinition;
use App\Domain\Game\Models\Game;
use App\Domain\User\Models\User;

class FiveWinStreakAchievement implements GameEndTriggerableAchievement
{
    private int $targetStreak = 5;

    public function id(): string
    {
        return 'five_win_streak';
    }

    public function definition(): AchievementDefinition
    {
        return new AchievementDefinition(
            id: $this->id(),
            name: 'Hot Streak',
            description: 'Win 5 games in a row',
            icon: '🔥',
            category: 'streaks',
        );
    }

    public function checkGameEnd(User $user, Game $game): ?AchievementContext
    {
        if ($game->winner_id !== $user->id) {
            return null;
        }

        $stats = $user->statistics;

        if (! $stats || $stats->current_win_streak !== $this->targetStreak) {
            return null;
        }

        return new AchievementContext(['streak' => $this->targetStreak]);
    }
}
