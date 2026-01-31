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

class RareFindAchievement implements MoveTriggerableAchievement
{
    private int $maxPlays = 10;

    public function id(): string
    {
        return 'rare_find';
    }

    public function definition(): AchievementDefinition
    {
        return new AchievementDefinition(
            id: $this->id(),
            name: 'Rare Find',
            description: 'Play a word that has been played fewer than 10 times',
            icon: '💎',
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

            if (! $dictionary || $dictionary->times_played > $this->maxPlays) {
                continue;
            }

            return new AchievementContext([
                'word' => $word,
                'times_played' => $dictionary->times_played,
            ]);
        }

        return null;
    }
}
