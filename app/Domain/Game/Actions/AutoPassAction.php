<?php

namespace App\Domain\Game\Actions;

use App\Domain\Game\Enums\MoveType;
use App\Domain\Game\Events\MovePlayed;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\Move;
use App\Domain\Game\Notifications\TurnTimedOutNotification;
use App\Domain\Game\Notifications\YourTurnNotification;
use App\Domain\Game\Support\Rules\RuleEngine;
use App\Domain\User\Models\User;

class AutoPassAction
{
    public function execute(Game $game): Move
    {
        /** @var User $timedOutUser */
        $timedOutUser = $game->currentTurnUser;

        $move = Move::create([
            'game_id' => $game->id,
            'user_id' => $timedOutUser->id,
            'tiles' => null,
            'words' => null,
            'score' => 0,
            'type' => MoveType::Pass,
        ]);

        $game->increment('consecutive_passes');

        $this->handleEndGameOrSwitchTurn($game);

        $freshGame = $game->fresh(['gamePlayers.user', 'currentTurnUser', 'winner', 'latestMove.user']);

        broadcast(new MovePlayed($freshGame, $move, $timedOutUser))->toOthers();

        $this->notifyPlayers($freshGame, $move, $timedOutUser);

        return $move;
    }

    private function notifyPlayers(Game $game, Move $move, User $timedOutPlayer): void
    {
        $timedOutPlayer->notify(new TurnTimedOutNotification($game));

        if ($game->isFinished()) {
            return;
        }

        $opponent = $game->getOpponent($timedOutPlayer);

        if ($game->shouldNotifyPlayer($opponent)) {
            $opponent->notify(new YourTurnNotification($game, $move, $timedOutPlayer, isAutoPass: true));
        }
    }

    private function handleEndGameOrSwitchTurn(Game $game): void
    {
        $ruleEngine = app(RuleEngine::class);
        $endGameRule = $ruleEngine->checkEndGame($game->fresh());

        if ($endGameRule) {
            app(EndGameAction::class)->execute($game);

            return;
        }

        app(SwitchTurnAction::class)->execute($game);
    }
}
