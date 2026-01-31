<?php

namespace App\Domain\Achievement\Achievements\GameMilestones;

use App\Domain\Achievement\Contracts\GameEndTriggerableAchievement;
use App\Domain\Achievement\Data\AchievementContext;
use App\Domain\Achievement\Data\AchievementDefinition;
use App\Domain\Game\Enums\MoveType;
use App\Domain\Game\Models\Game;
use App\Domain\User\Models\User;

class NoPassesAchievement implements GameEndTriggerableAchievement
{
    public function id(): string
    {
        return 'no_passes';
    }

    public function definition(): AchievementDefinition
    {
        return new AchievementDefinition(
            id: $this->id(),
            name: 'All In',
            description: 'Complete a game without passing',
            icon: '💪',
            category: 'game_milestones',
        );
    }

    public function checkGameEnd(User $user, Game $game): ?AchievementContext
    {
        $passCount = $game->moves()
            ->where('user_id', $user->id)
            ->where('type', MoveType::Pass)
            ->count();

        if ($passCount > 0) {
            return null;
        }

        // Must have played at least one move
        $playCount = $game->moves()
            ->where('user_id', $user->id)
            ->where('type', MoveType::Play)
            ->count();

        if ($playCount === 0) {
            return null;
        }

        return new AchievementContext(['moves_played' => $playCount]);
    }
}
