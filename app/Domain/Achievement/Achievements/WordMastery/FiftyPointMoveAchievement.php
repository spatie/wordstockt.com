<?php

namespace App\Domain\Achievement\Achievements\WordMastery;

use App\Domain\Achievement\Contracts\MoveTriggerableAchievement;
use App\Domain\Achievement\Data\AchievementContext;
use App\Domain\Achievement\Data\AchievementDefinition;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\Move;
use App\Domain\Game\Support\Scoring\ScoringResult;
use App\Domain\User\Models\User;

class FiftyPointMoveAchievement implements MoveTriggerableAchievement
{
    private int $minScore = 50;

    public function id(): string
    {
        return 'fifty_point_move';
    }

    public function definition(): AchievementDefinition
    {
        return new AchievementDefinition(
            id: $this->id(),
            name: 'Big Score',
            description: 'Score 50 or more points in a single move',
            icon: '💯',
            category: 'word_mastery',
        );
    }

    public function checkMove(
        User $user,
        Move $move,
        Game $game,
        ScoringResult $scoringResult,
    ): ?AchievementContext {
        if ($move->score < $this->minScore) {
            return null;
        }

        return new AchievementContext([
            'score' => $move->score,
            'words' => $move->words,
        ]);
    }
}
