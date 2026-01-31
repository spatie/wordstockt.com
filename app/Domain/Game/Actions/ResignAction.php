<?php

namespace App\Domain\Game\Actions;

use App\Domain\Game\Actions\Stats\UpdateGameEndStatsAction;
use App\Domain\Game\Enums\GameAction;
use App\Domain\Game\Enums\GameStatus;
use App\Domain\Game\Enums\MoveType;
use App\Domain\Game\Events\MovePlayed;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\Move;
use App\Domain\Game\Notifications\GameFinishedNotification;
use App\Domain\Game\Support\Rules\RuleEngine;
use App\Domain\User\Models\User;

class ResignAction
{
    public function execute(Game $game, User $user): Move
    {
        $ruleEngine = app(RuleEngine::class);
        $ruleEngine->validateActionOrFail($game, $user, GameAction::Resign);

        $move = Move::create([
            'game_id' => $game->id,
            'user_id' => $user->id,
            'tiles' => null,
            'words' => null,
            'score' => 0,
            'type' => MoveType::Resign,
        ]);

        /** @var User|null $winner */
        $winner = $game->players()->where('users.id', '!=', $user->id)->first();

        $game->update([
            'status' => GameStatus::Finished,
            'winner_id' => $winner?->id,
        ]);

        $this->updatePlayerStats($game);

        app(UpdateGameEndStatsAction::class)->execute($game);

        $freshGame = $game->fresh(['gamePlayers.user', 'currentTurnUser', 'winner', 'latestMove.user', 'players']);

        broadcast(new MovePlayed($freshGame, $move, $user))->toOthers();

        if ($winner) {
            $winner->notify(new GameFinishedNotification($freshGame, wasResign: true, resignedPlayer: $user));
        }

        return $move;
    }

    private function updatePlayerStats(Game $game): void
    {
        $playerIds = $game->players()->pluck('users.id');

        User::whereIn('id', $playerIds)->increment('games_played');

        if ($game->winner_id) {
            User::where('id', $game->winner_id)->increment('games_won');
        }
    }
}
