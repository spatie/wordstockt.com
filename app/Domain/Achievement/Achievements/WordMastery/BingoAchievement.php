<?php

namespace App\Domain\Achievement\Achievements\WordMastery;

use App\Domain\Achievement\Contracts\MoveTriggerableAchievement;
use App\Domain\Achievement\Data\AchievementContext;
use App\Domain\Achievement\Data\AchievementDefinition;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\Move;
use App\Domain\Game\Support\Scoring\ScoringResult;
use App\Domain\User\Models\User;

class BingoAchievement implements MoveTriggerableAchievement
{
    public function id(): string
    {
        return 'bingo';
    }

    public function definition(): AchievementDefinition
    {
        return new AchievementDefinition(
            id: $this->id(),
            name: 'Bingo!',
            description: 'Use all 7 tiles in a single move',
            icon: '7️⃣',
            category: 'word_mastery',
        );
    }

    public function checkMove(
        User $user,
        Move $move,
        Game $game,
        ScoringResult $scoringResult,
    ): ?AchievementContext {
        if (! $scoringResult->hasBonus('scoring.bingo_bonus')) {
            return null;
        }

        return new AchievementContext([
            'score' => $move->score,
            'words' => $move->words,
        ]);
    }
}
