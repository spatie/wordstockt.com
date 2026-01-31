<?php

namespace App\Domain\Achievement\Achievements\Scoring;

use App\Domain\Achievement\Contracts\GameEndTriggerableAchievement;
use App\Domain\Achievement\Data\AchievementContext;
use App\Domain\Achievement\Data\AchievementDefinition;
use App\Domain\Game\Models\Game;
use App\Domain\User\Models\User;

class NailBiterAchievement implements GameEndTriggerableAchievement
{
    private int $maxMargin = 5;

    public function id(): string
    {
        return 'nail_biter';
    }

    public function definition(): AchievementDefinition
    {
        return new AchievementDefinition(
            id: $this->id(),
            name: 'Nail Biter',
            description: 'Win by less than 5 points',
            icon: '😰',
            category: 'scoring',
        );
    }

    public function checkGameEnd(User $user, Game $game): ?AchievementContext
    {
        if ($game->winner_id !== $user->id) {
            return null;
        }

        $myScore = $game->getPlayerScore($user);
        $opponent = $game->getOpponent($user);

        if (! $opponent) {
            return null;
        }

        $opponentScore = $game->getPlayerScore($opponent);
        $margin = $myScore - $opponentScore;

        if ($margin <= 0 || $margin >= $this->maxMargin) {
            return null;
        }

        return new AchievementContext([
            'score' => $myScore,
            'opponent_score' => $opponentScore,
            'margin' => $margin,
        ]);
    }
}
