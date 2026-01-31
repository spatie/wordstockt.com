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

class TrailblazerAchievement implements MoveTriggerableAchievement
{
    private int $targetFirstPlays = 5;

    public function id(): string
    {
        return 'trailblazer';
    }

    public function definition(): AchievementDefinition
    {
        return new AchievementDefinition(
            id: $this->id(),
            name: 'Trailblazer',
            description: 'Be the first to play 5 different words',
            icon: '🔦',
            category: 'word_frequency',
        );
    }

    public function checkMove(
        User $user,
        Move $move,
        Game $game,
        ScoringResult $scoringResult,
    ): ?AchievementContext {
        $firstPlayCount = Dictionary::where('first_played_by_user_id', $user->id)->count();

        if ($firstPlayCount !== $this->targetFirstPlays) {
            return null;
        }

        return new AchievementContext(['first_plays' => $this->targetFirstPlays]);
    }
}
