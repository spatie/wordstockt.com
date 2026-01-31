<?php

namespace App\Domain\Achievement\Achievements\WordFrequency;

use App\Domain\Achievement\Contracts\MoveTriggerableAchievement;
use App\Domain\Achievement\Data\AchievementContext;
use App\Domain\Achievement\Data\AchievementDefinition;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\Move;
use App\Domain\Game\Support\Scoring\ScoringResult;
use App\Domain\Support\Models\Dictionary;
use App\Domain\User\Models\User;

class PioneerAchievement implements MoveTriggerableAchievement
{
    public function id(): string
    {
        return 'pioneer';
    }

    public function definition(): AchievementDefinition
    {
        return new AchievementDefinition(
            id: $this->id(),
            name: 'Pioneer',
            description: 'Be the first player ever to play a word',
            icon: '🏴',
            category: 'word_frequency',
        );
    }

    public function checkMove(
        User $user,
        Move $move,
        Game $game,
        ScoringResult $scoringResult,
    ): ?AchievementContext {
        $words = $move->words ?? [];
        $language = $game->language;

        foreach ($words as $word) {
            $dictionary = Dictionary::where('language', $language)
                ->where('word', strtoupper($word))
                ->first();

            if ($dictionary && $dictionary->first_played_by_user_id === $user->id && $dictionary->times_played === 1) {
                return new AchievementContext(['word' => $word]);
            }
        }

        return null;
    }
}
