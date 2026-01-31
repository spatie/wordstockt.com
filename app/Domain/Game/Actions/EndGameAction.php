<?php

namespace App\Domain\Game\Actions;

use App\Domain\Achievement\Actions\CheckGameEndAchievementsAction;
use App\Domain\Game\Actions\Stats\UpdateGameEndStatsAction;
use App\Domain\Game\Enums\GameStatus;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\GamePlayer;
use App\Domain\Game\Notifications\GameFinishedNotification;
use App\Domain\Game\Support\Scoring\ScoringEngine;
use App\Domain\User\Models\User;
use Illuminate\Support\Collection;

class EndGameAction
{
    public function execute(Game $game): void
    {
        $gamePlayers = $game->gamePlayers()->with('user')->get();

        $this->applyEndGamePenalties($gamePlayers);

        $winner = $this->determineWinner($gamePlayers);

        $game->update([
            'status' => GameStatus::Finished,
            'winner_id' => $winner->user_id,
        ]);

        $hasGuestPlayer = $gamePlayers->contains(fn ($gp) => $gp->user->isGuest());

        if (! $hasGuestPlayer) {
            $this->updatePlayerStats($game);
            app(UpdateGameEndStatsAction::class)->execute($game);
            $this->checkGameEndAchievements($game, $gamePlayers);
        }

        $this->notifyPlayers($game->fresh(['gamePlayers.user', 'players']));
    }

    private function notifyPlayers(Game $game): void
    {
        $game->players->each(function ($player) use ($game): void {
            /** @var User $player */
            $player->notify(new GameFinishedNotification($game));
        });
    }

    private function applyEndGamePenalties(Collection $gamePlayers): void
    {
        $scoringEngine = app(ScoringEngine::class);

        $gamePlayers->each(function (GamePlayer $player) use ($scoringEngine): void {
            $penalty = $scoringEngine->calculateEndGamePenalty($player->rack_tiles ?? []);
            $newScore = $player->score - $penalty;

            $player->update(['score' => max(0, $newScore)]);
        });
    }

    private function determineWinner(Collection $gamePlayers): GamePlayer
    {
        return $gamePlayers->sortByDesc('score')->first();
    }

    private function updatePlayerStats(Game $game): void
    {
        $playerIds = $game->players()->pluck('users.id');

        User::whereIn('id', $playerIds)->increment('games_played');

        if ($game->winner_id) {
            User::where('id', $game->winner_id)->increment('games_won');
        }
    }

    private function checkGameEndAchievements(Game $game, Collection $gamePlayers): void
    {
        $checkAction = app(CheckGameEndAchievementsAction::class);
        $freshGame = $game->fresh();

        foreach ($gamePlayers as $gamePlayer) {
            $checkAction->execute($gamePlayer->user, $freshGame);
        }
    }
}
