<?php

namespace App\Domain\Achievement\Achievements\GameMilestones;

use App\Domain\Achievement\Contracts\GameEndTriggerableAchievement;
use App\Domain\Achievement\Data\AchievementContext;
use App\Domain\Achievement\Data\AchievementDefinition;
use App\Domain\Game\Models\Game;
use App\Domain\User\Models\User;

class TenWinStreakAchievement implements GameEndTriggerableAchievement
{
    private int $targetStreak = 10;

    public function id(): string
    {
        return 'ten_win_streak';
    }

    public function definition(): AchievementDefinition
    {
        return new AchievementDefinition(
            id: $this->id(),
            name: 'Unstoppable',
            description: 'Win 10 games in a row',
            icon: '🔥',
            category: 'game_milestones',
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
