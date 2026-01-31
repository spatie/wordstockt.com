<?php

namespace App\Domain\Achievement\Achievements\Fun;

use App\Domain\Achievement\Contracts\MoveTriggerableAchievement;
use App\Domain\Achievement\Data\AchievementContext;
use App\Domain\Achievement\Data\AchievementDefinition;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\Move;
use App\Domain\Game\Support\Scoring\ScoringResult;
use App\Domain\User\Models\User;

class StrongOpeningAchievement implements MoveTriggerableAchievement
{
    private int $minScore = 30;

    public function id(): string
    {
        return 'strong_opening';
    }

    public function definition(): AchievementDefinition
    {
        return new AchievementDefinition(
            id: $this->id(),
            name: 'Strong Start',
            description: 'Score 30 or more points on the opening move',
            icon: '🚀',
            category: 'fun',
        );
    }

    public function checkMove(
        User $user,
        Move $move,
        Game $game,
        ScoringResult $scoringResult,
    ): ?AchievementContext {
        // Check if this is the first move of the game
        $isFirstMove = $game->moves()->count() === 1;

        if (! $isFirstMove) {
            return null;
        }

        if ($move->score < $this->minScore) {
            return null;
        }

        return new AchievementContext([
            'score' => $move->score,
            'words' => $move->words,
        ]);
    }
}
