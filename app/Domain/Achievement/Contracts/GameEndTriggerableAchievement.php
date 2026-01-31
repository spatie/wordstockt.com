<?php

namespace App\Domain\Achievement\Contracts;

use App\Domain\Achievement\Data\AchievementContext;
use App\Domain\Game\Models\Game;
use App\Domain\User\Models\User;

interface GameEndTriggerableAchievement extends Achievement
{
    /**
     * Check if this achievement should be unlocked based on game end state.
     * Returns context data if unlocked, null if not.
     */
    public function checkGameEnd(User $user, Game $game): ?AchievementContext;
}
