<?php

namespace App\Domain\Achievement\Achievements\Fun;

use App\Domain\Achievement\Contracts\MoveTriggerableAchievement;
use App\Domain\Achievement\Data\AchievementContext;
use App\Domain\Achievement\Data\AchievementDefinition;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\Move;
use App\Domain\Game\Support\Scoring\ScoringResult;
use App\Domain\User\Models\User;

class PalindromeWordAchievement implements MoveTriggerableAchievement
{
    private int $minLength = 3;

    public function id(): string
    {
        return 'palindrome';
    }

    public function definition(): AchievementDefinition
    {
        return new AchievementDefinition(
            id: $this->id(),
            name: 'Mirror Mirror',
            description: 'Play a palindrome word',
            icon: '🪞',
            category: 'fun',
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
            if (strlen($word) >= $this->minLength && $this->isPalindrome($word)) {
                return new AchievementContext(['word' => $word]);
            }
        }

        return null;
    }

    private function isPalindrome(string $word): bool
    {
        $word = strtolower($word);

        return $word === strrev($word);
    }
}
