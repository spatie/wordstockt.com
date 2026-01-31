<?php

namespace App\Domain\Achievement\Achievements\Scoring;

use App\Domain\Achievement\Contracts\GameEndTriggerableAchievement;
use App\Domain\Achievement\Data\AchievementContext;
use App\Domain\Achievement\Data\AchievementDefinition;
use App\Domain\Game\Models\Game;
use App\Domain\User\Models\User;

class DoubleCenturyAchievement implements GameEndTriggerableAchievement
{
    private int $minScore = 200;

    public function id(): string
    {
        return 'double_century';
    }

    public function definition(): AchievementDefinition
    {
        return new AchievementDefinition(
            id: $this->id(),
            name: 'Double Century',
            description: 'Score 200 or more points in a game',
            icon: '🌟',
            category: 'scoring',
        );
    }

    public function checkGameEnd(User $user, Game $game): ?AchievementContext
    {
        $score = $game->getPlayerScore($user);

        if ($score < $this->minScore) {
            return null;
        }

        return new AchievementContext(['score' => $score]);
    }
}
