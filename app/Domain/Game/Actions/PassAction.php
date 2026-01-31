<?php

namespace App\Domain\Game\Actions;

use App\Domain\Game\Enums\GameAction;
use App\Domain\Game\Enums\MoveType;
use App\Domain\Game\Events\MovePlayed;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\Move;
use App\Domain\Game\Notifications\YourTurnNotification;
use App\Domain\Game\Support\Rules\RuleEngine;
use App\Domain\User\Models\User;

class PassAction
{
    public function execute(Game $game, User $user): Move
    {
        $ruleEngine = app(RuleEngine::class);

        $ruleEngine->validateActionOrFail($game, $user, GameAction::Pass);

        $move = Move::create([
            'game_id' => $game->id,
            'user_id' => $user->id,
            'tiles' => null,
            'words' => null,
            'score' => 0,
            'type' => MoveType::Pass,
        ]);

        $game->increment('consecutive_passes');

        $this->handleEndGameOrSwitchTurn($game, $ruleEngine);

        $freshGame = $game->fresh(['gamePlayers.user', 'currentTurnUser', 'winner', 'latestMove.user']);

        broadcast(new MovePlayed($freshGame, $move, $user))->toOthers();

        $this->notifyOpponent($freshGame, $move, $user);

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

    private function handleEndGameOrSwitchTurn(Game $game, RuleEngine $ruleEngine): void
    {
        $endGameRule = $ruleEngine->checkEndGame($game->fresh());

        if ($endGameRule instanceof \App\Domain\Game\Support\Rules\EndGame\EndGameRule) {
            app(EndGameAction::class)->execute($game);

            return;
        }

        app(SwitchTurnAction::class)->execute($game);

        // Check again after switching turns in case the end game condition is now met
        $endGameRule = $ruleEngine->checkEndGame($game->fresh());

        if ($endGameRule instanceof \App\Domain\Game\Support\Rules\EndGame\EndGameRule) {
            app(EndGameAction::class)->execute($game);
        }
    }
}
