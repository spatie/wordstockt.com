<?php

namespace App\Domain\Achievement\Achievements\WordFrequency;

use App\Domain\Achievement\Contracts\MoveTriggerableAchievement;
use App\Domain\Achievement\Data\AchievementContext;
use App\Domain\Achievement\Data\AchievementDefinition;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\Move;
use App\Domain\Game\Support\Scoring\ScoringResult;
use App\Domain\User\Models\User;

class Vocabulary1000Achievement implements MoveTriggerableAchievement
{
    private int $targetWords = 1000;

    public function id(): string
    {
        return 'vocabulary_1000';
    }

    public function definition(): AchievementDefinition
    {
        return new AchievementDefinition(
            id: $this->id(),
            name: 'Lexicon Master',
            description: 'Play 1000 unique different words',
            icon: '🎓',
            category: 'word_frequency',
        );
    }

    public function checkMove(
        User $user,
        Move $move,
        Game $game,
        ScoringResult $scoringResult,
    ): ?AchievementContext {
        $stats = $user->statistics;

        if (! $stats || $stats->unique_words_played !== $this->targetWords) {
            return null;
        }

        return new AchievementContext(['unique_words' => $this->targetWords]);
    }
}
