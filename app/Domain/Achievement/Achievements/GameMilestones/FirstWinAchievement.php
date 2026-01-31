<?php

namespace App\Domain\Achievement\Achievements\GameMilestones;

use App\Domain\Achievement\Contracts\GameEndTriggerableAchievement;
use App\Domain\Achievement\Data\AchievementContext;
use App\Domain\Achievement\Data\AchievementDefinition;
use App\Domain\Game\Models\Game;
use App\Domain\User\Models\User;

class FirstWinAchievement implements GameEndTriggerableAchievement
{
    public function id(): string
    {
        return 'first_win';
    }

    public function definition(): AchievementDefinition
    {
        return new AchievementDefinition(
            id: $this->id(),
            name: 'First Victory',
            description: 'Win your first game',
            icon: '🏆',
            category: 'game_milestones',
        );
    }

    public function checkGameEnd(User $user, Game $game): ?AchievementContext
    {
        if ($game->winner_id !== $user->id) {
            return null;
        }

        // Only award if this is their first win (games_won was just incremented to 1)
        if ($user->games_won !== 1) {
            return null;
        }

        return new AchievementContext;
    }
}
