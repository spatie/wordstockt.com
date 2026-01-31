<?php

declare(strict_types=1);

namespace App\Domain\Game\Support\Rules\Turn;

use App\Domain\Game\Data\Move;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Support\Rules\RuleResult;

class TilesInRackRule extends TurnRule
{
    public function validate(Game $game, Move $move, array $board): RuleResult
    {
        $currentPlayer = $game->gamePlayers()
            ->where('user_id', $game->current_turn_user_id)
            ->first();

        if (! $currentPlayer) {
            return RuleResult::fail(
                $this->getIdentifier(),
                'Could not find current player.'
            );
        }

        $rack = $currentPlayer->rack_tiles ?? [];
        $rackCopy = $rack;

        foreach ($move->tiles as $tile) {
            $found = false;

            foreach ($rackCopy as $index => $rackTile) {
                if (! $this->tilesMatch($tile, $rackTile)) {
                    continue;
                }

                $found = true;
                unset($rackCopy[$index]);
                break;
            }

            if (! $found) {
                return RuleResult::fail(
                    $this->getIdentifier(),
                    "You don't have the tile '{$tile['letter']}' in your rack."
                );
            }
        }

        return RuleResult::pass($this->getIdentifier());
    }

    private function tilesMatch(array $tile, array $rackTile): bool
    {
        if ($tile['is_blank']) {
            return ($rackTile['is_blank'] ?? false) || $rackTile['letter'] === '*';
        }

        if ($rackTile['letter'] !== $tile['letter']) {
            return false;
        }

        return $rackTile['points'] === $tile['points'];
    }
}
