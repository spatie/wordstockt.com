<?php

namespace App\Domain\Achievement\Achievements\WordMastery;

use App\Domain\Achievement\Contracts\MoveTriggerableAchievement;
use App\Domain\Achievement\Data\AchievementContext;
use App\Domain\Achievement\Data\AchievementDefinition;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\Move;
use App\Domain\Game\Support\Scoring\ScoringResult;
use App\Domain\User\Models\User;

class SevenLetterWordAchievement implements MoveTriggerableAchievement
{
    private int $minLetters = 7;

    public function id(): string
    {
        return 'seven_letter_word';
    }

    public function definition(): AchievementDefinition
    {
        return new AchievementDefinition(
            id: $this->id(),
            name: 'Wordsmith',
            description: 'Play a word with 7 or more letters',
            icon: '📝',
            category: 'word_mastery',
        );
    }

    public function checkMove(
        User $user,
        Move $move,
        Game $game,
        ScoringResult $scoringResult,
    ): ?AchievementContext {
        $words = $move->words ?? [];

        foreach ($words as $word) {
            if (strlen($word) >= $this->minLetters) {
                return new AchievementContext(['word' => $word]);
            }
        }

        return null;
    }
}
