<?php

namespace App\Domain\Achievement\Achievements\GameMilestones;

use App\Domain\Achievement\Contracts\GameEndTriggerableAchievement;
use App\Domain\Achievement\Data\AchievementContext;
use App\Domain\Achievement\Data\AchievementDefinition;
use App\Domain\Game\Models\Game;
use App\Domain\User\Models\User;

class FiftyWinsAchievement implements GameEndTriggerableAchievement
{
    private int $targetWins = 50;

    public function id(): string
    {
        return 'fifty_wins';
    }

    public function definition(): AchievementDefinition
    {
        return new AchievementDefinition(
            id: $this->id(),
            name: 'Champion',
            description: 'Win 50 games',
            icon: '🏅',
            category: 'game_milestones',
        );
    }

    public function checkGameEnd(User $user, Game $game): ?AchievementContext
    {
        if ($game->winner_id !== $user->id) {
            return null;
        }

        if ($user->games_won !== $this->targetWins) {
            return null;
        }

        return new AchievementContext(['wins' => $this->targetWins]);
    }
}
