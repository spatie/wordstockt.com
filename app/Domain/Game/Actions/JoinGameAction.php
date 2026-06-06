<?php

namespace App\Domain\Game\Actions;

use App\Domain\Game\Exceptions\GameException;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\GamePlayer;
use App\Domain\Game\Support\TileBag;
use App\Domain\User\Models\User;
use Illuminate\Support\Lottery;

class JoinGameAction
{
    public function execute(Game $game, User $user): Game
    {
        /** @var User|null $creator */
        $creator = $game->players()->first();
        if ($creator?->id === $user->id) {
            throw GameException::cannotPlayAgainstSelf();
        }

        $tileBag = TileBag::fromArray($game->tile_bag);

        $turnOrder = $game->gamePlayers()->count() + 1;

        $gamePlayer = GamePlayer::create([
            'game_id' => $game->id,
            'user_id' => $user->id,
            'rack_tiles' => [],
            'score' => 0,
            'turn_order' => $turnOrder,
            'has_received_blank' => false,
        ]);

        $tiles = $tileBag->drawForRack([], 7);
        $tiles = $this->maybeGiveBlank($tiles, $gamePlayer, $tileBag);
        $gamePlayer->setRackTiles(TileBag::tilesToArray($tiles));

        $game->update(['tile_bag' => $tileBag->toArray()]);

        $freshGame = $game->fresh(['players', 'gamePlayers']);

        if ($freshGame->gamePlayers()->count() >= $freshGame->max_players) {
            return app(ActivateGameAction::class)->execute($freshGame);
        }

        return $freshGame;
    }

    private function maybeGiveBlank(array $tiles, GamePlayer $gamePlayer, TileBag $tileBag): array
    {
        if ($gamePlayer->has_received_blank) {
            return $tiles;
        }

        if ($tiles === []) {
            return $tiles;
        }

        if (! $this->shouldGiveBlank($tileBag)) {
            return $tiles;
        }

        $gamePlayer->update(['has_received_blank' => true]);

        return $tileBag->swapOneForBlank($tiles);
    }

    private function shouldGiveBlank(TileBag $tileBag): bool
    {
        if ($tileBag->isEmpty()) {
            return true;
        }

        return Lottery::odds(1, 10)->choose();
    }
}
