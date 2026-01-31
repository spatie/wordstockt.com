<?php

declare(strict_types=1);

namespace App\Domain\Game\Support\Rules\EndGame;

use App\Domain\Game\Models\Game;

class EmptyRackRule extends EndGameRule
{
    public function shouldEndGame(Game $game): bool
    {
        if (! empty($game->tile_bag)) {
            return false;
        }

        $playersWithEmptyRack = $game->gamePlayers
            ->filter(fn ($gamePlayer): bool => empty($gamePlayer->rack_tiles));

        if ($playersWithEmptyRack->isEmpty()) {
            return false;
        }

        // If both players have empty racks, end the game immediately
        if ($playersWithEmptyRack->count() >= 2) {
            return true;
        }

        $playerWithEmptyRack = $playersWithEmptyRack->first();

        // The opponent gets one final move after a player empties their rack.
        // Game ends only when it's no longer the empty-rack player's turn
        // (meaning the opponent already had their chance to play).
        return $playerWithEmptyRack->user_id !== $game->current_turn_user_id;
    }

    public function getEndReason(): string
    {
        return 'A player emptied their rack with no tiles remaining in the bag.';
    }
}
