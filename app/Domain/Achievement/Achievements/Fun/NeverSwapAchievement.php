<?php

namespace App\Domain\Achievement\Achievements\Fun;

use App\Domain\Achievement\Contracts\GameEndTriggerableAchievement;
use App\Domain\Achievement\Data\AchievementContext;
use App\Domain\Achievement\Data\AchievementDefinition;
use App\Domain\Game\Enums\MoveType;
use App\Domain\Game\Models\Game;
use App\Domain\User\Models\User;

class NeverSwapAchievement implements GameEndTriggerableAchievement
{
    public function id(): string
    {
        return 'never_swap';
    }

    public function definition(): AchievementDefinition
    {
        return new AchievementDefinition(
            id: $this->id(),
            name: 'Purist',
            description: 'Win a game without swapping tiles',
            icon: '🧘',
            category: 'fun',
        );
    }

    public function checkGameEnd(User $user, Game $game): ?AchievementContext
    {
        if ($game->winner_id !== $user->id) {
            return null;
        }

        $swapCount = $game->moves()
            ->where('user_id', $user->id)
            ->where('type', MoveType::Swap)
            ->count();

        if ($swapCount > 0) {
            return null;
        }

        return new AchievementContext;
    }
}
