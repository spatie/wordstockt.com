<?php

namespace App\Domain\Achievement\Achievements\Fun;

use App\Domain\Achievement\Contracts\GameEndTriggerableAchievement;
use App\Domain\Achievement\Data\AchievementContext;
use App\Domain\Achievement\Data\AchievementDefinition;
use App\Domain\Game\Enums\MoveType;
use App\Domain\Game\Models\Game;
use App\Domain\User\Models\User;

class LastTilesWinAchievement implements GameEndTriggerableAchievement
{
    public function id(): string
    {
        return 'last_tiles_win';
    }

    public function definition(): AchievementDefinition
    {
        return new AchievementDefinition(
            id: $this->id(),
            name: 'Photo Finish',
            description: 'Win by playing your last tiles',
            icon: '📸',
            category: 'fun',
        );
    }

    public function checkGameEnd(User $user, Game $game): ?AchievementContext
    {
        if ($game->winner_id !== $user->id) {
            return null;
        }

        // Get the user's last move
        $lastMove = $game->moves()
            ->where('user_id', $user->id)
            ->where('type', MoveType::Play)
            ->latest()
            ->first();

        if (! $lastMove) {
            return null;
        }

        // Check if the user's rack is empty (they played all tiles)
        $gamePlayer = $game->gamePlayers()->where('user_id', $user->id)->first();

        if (! $gamePlayer) {
            return null;
        }

        $rackTiles = $gamePlayer->rack_tiles ?? [];

        if (count($rackTiles) !== 0) {
            return null;
        }

        return new AchievementContext([
            'final_move_score' => $lastMove->score,
            'final_words' => $lastMove->words,
        ]);
    }
}
