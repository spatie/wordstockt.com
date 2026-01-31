<?php

namespace App\Domain\Game\Actions;

use App\Domain\Game\Enums\GameAction;
use App\Domain\Game\Enums\MoveType;
use App\Domain\Game\Events\MovePlayed;
use App\Domain\Game\Exceptions\InvalidMoveException;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\GamePlayer;
use App\Domain\Game\Models\Move;
use App\Domain\Game\Notifications\YourTurnNotification;
use App\Domain\Game\Support\Rules\RuleEngine;
use App\Domain\Game\Support\TileBag;
use App\Domain\User\Models\User;
use Illuminate\Support\Lottery;

class SwapTilesAction
{
    public function execute(Game $game, User $user, array $tilesToSwap): Move
    {
        $ruleEngine = app(RuleEngine::class);
        $ruleEngine->validateActionOrFail($game, $user, GameAction::Swap);

        $gamePlayer = $game->getGamePlayer($user);
        $this->validateTilesInRack($gamePlayer, $tilesToSwap);

        $tileBag = TileBag::fromArray($game->tile_bag);

        $drawnTiles = $tileBag->draw(count($tilesToSwap));
        $drawnTiles = $this->maybeGiveBlank($drawnTiles, $gamePlayer, $tileBag);

        $newRack = collect($gamePlayer->removeTilesFromRack($tilesToSwap))
            ->merge(TileBag::tilesToArray($drawnTiles))
            ->all();

        $tilesToReturn = collect($tilesToSwap)
            ->reject(fn ($tile) => $tile['is_blank'] ?? false)
            ->values()
            ->all();

        $tileBag->returnTiles($tilesToReturn);
        $gamePlayer->setRackTiles($newRack);

        $move = Move::create([
            'game_id' => $game->id,
            'user_id' => $user->id,
            'tiles' => $tilesToSwap,
            'words' => null,
            'score' => 0,
            'type' => MoveType::Swap,
        ]);

        $game->update([
            'tile_bag' => $tileBag->toArray(),
            'consecutive_passes' => 0,
        ]);

        $turnSwitched = false;

        if ($gamePlayer->has_free_swap) {
            $gamePlayer->useFreeSwap();
        } else {
            app(SwitchTurnAction::class)->execute($game);
            $turnSwitched = true;
        }

        $freshGame = $game->fresh(['gamePlayers.user', 'currentTurnUser', 'winner', 'latestMove.user']);

        broadcast(new MovePlayed($freshGame, $move, $user))->toOthers();

        if ($turnSwitched) {
            $this->notifyOpponent($freshGame, $move, $user);
        }

        return $move;
    }

    private function notifyOpponent(Game $game, Move $move, User $currentPlayer): void
    {
        if ($game->isFinished()) {
            return;
        }

        $opponent = $game->getOpponent($currentPlayer);

        if ($game->shouldNotifyPlayer($opponent)) {
            $opponent->notify(new YourTurnNotification($game, $move, $currentPlayer));
        }
    }

    private function validateTilesInRack(GamePlayer $gamePlayer, array $tiles): void
    {
        $rackLetters = collect($gamePlayer->rack_tiles ?? [])->pluck('letter');

        collect($tiles)->each(function (array $tile) use (&$rackLetters): void {
            $key = $rackLetters->search($tile['letter']);

            if ($key === false) {
                throw InvalidMoveException::tilesNotInRack();
            }

            $rackLetters->forget($key);
        });
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
