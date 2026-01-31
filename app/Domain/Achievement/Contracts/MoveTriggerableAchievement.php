<?php

namespace App\Domain\Achievement\Contracts;

use App\Domain\Achievement\Data\AchievementContext;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\Move;
use App\Domain\Game\Support\Scoring\ScoringResult;
use App\Domain\User\Models\User;

interface MoveTriggerableAchievement extends Achievement
{
    /**
     * Check if this achievement should be unlocked based on the move.
     * Returns context data if unlocked, null if not.
     */
    public function checkMove(
        User $user,
        Move $move,
        Game $game,
        ScoringResult $scoringResult,
    ): ?AchievementContext;
}
