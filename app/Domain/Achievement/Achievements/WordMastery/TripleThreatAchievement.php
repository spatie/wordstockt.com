<?php

namespace App\Domain\Achievement\Achievements\WordMastery;

use App\Domain\Achievement\Contracts\GameEndTriggerableAchievement;
use App\Domain\Achievement\Data\AchievementContext;
use App\Domain\Achievement\Data\AchievementDefinition;
use App\Domain\Game\Models\Game;
use App\Domain\User\Models\User;

class TripleThreatAchievement implements GameEndTriggerableAchievement
{
    private array $tripleWordPositions = [
        [0, 0], [0, 7], [0, 14],
        [7, 0], [7, 14],
        [14, 0], [14, 7], [14, 14],
    ];

    public function id(): string
    {
        return 'triple_threat';
    }

    public function definition(): AchievementDefinition
    {
        return new AchievementDefinition(
            id: $this->id(),
            name: 'Triple Threat',
            description: 'Use all triple word squares in a single game',
            icon: '3️⃣',
            category: 'word_mastery',
        );
    }

    public function checkGameEnd(User $user, Game $game): ?AchievementContext
    {
        $boardState = $game->board_state ?? [];

        $allTripleWordSquaresUsed = collect($this->tripleWordPositions)
            ->every(fn (array $position): bool => $this->hasLetterAt($boardState, $position[0], $position[1]));

        if (! $allTripleWordSquaresUsed) {
            return null;
        }

        return new AchievementContext(['triple_words_used' => count($this->tripleWordPositions)]);
    }

    private function hasLetterAt(array $boardState, int $x, int $y): bool
    {
        $cell = $boardState[$y][$x] ?? null;

        return is_array($cell) && isset($cell['letter']);
    }
}
