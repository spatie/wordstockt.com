<?php

namespace App\Domain\Game\Actions;

use App\Domain\Achievement\Actions\CheckMoveAchievementsAction;
use App\Domain\Game\Actions\Stats\UpdateMoveStatsAction;
use App\Domain\Game\Data\Move as MoveData;
use App\Domain\Game\Enums\GameAction;
use App\Domain\Game\Enums\MoveType;
use App\Domain\Game\Events\MovePlayed;
use App\Domain\Game\Exceptions\InvalidMoveException;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\GamePlayer;
use App\Domain\Game\Models\Move;
use App\Domain\Game\Notifications\YourTurnNotification;
use App\Domain\Game\Support\Board;
use App\Domain\Game\Support\Rules\RuleEngine;
use App\Domain\Game\Support\Scoring\ScoringEngine;
use App\Domain\Game\Support\TileBag;
use App\Domain\Support\Models\Dictionary;
use App\Domain\User\Models\User;
use Illuminate\Support\Lottery;

class PlayMoveAction
{
    private const EMPTY_RACK_BONUS = 25;

    public function execute(Game $game, User $user, array $tiles): Move
    {
        $ruleEngine = app(RuleEngine::class);

        $ruleEngine->validateActionOrFail($game, $user, GameAction::Play);

        $this->validateMoveRules($game, $tiles, $ruleEngine);

        $boardService = app(Board::class);
        $scoringEngine = app(ScoringEngine::class);

        $gamePlayer = $game->getGamePlayer($user);
        $bagWasEmpty = empty($game->tile_bag);

        $newBoard = $boardService->placeTiles($game->board_state, $tiles);
        $words = $boardService->findFormedWords($newBoard, $tiles);
        $scoringResult = $scoringEngine->calculateMoveScore($game, $words, $tiles, $boardService);
        $score = $scoringResult->getTotal();

        $tileBag = $this->refillPlayerRack($game, $gamePlayer, $tiles);

        // Grant empty rack bonus immediately when player clears rack with empty bag
        if ($bagWasEmpty && empty($gamePlayer->rack_tiles)) {
            $score += self::EMPTY_RACK_BONUS;
            $gamePlayer->update(['received_empty_rack_bonus' => true]);
        }

        $this->updatePlayerScore($gamePlayer, $score);

        $move = $this->createMoveRecord($game, $user, $tiles, $words, $score);

        $this->recordWordsPlayed($words, $game->language);

        $this->updateGameState($game, $newBoard, $tileBag);
        $this->handleEndGameOrSwitchTurn($game, $ruleEngine);

        $freshGame = $game->fresh(['gamePlayers.user', 'currentTurnUser', 'winner', 'latestMove.user']);

        broadcast(new MovePlayed($freshGame, $move, $user))->toOthers();
        $this->notifyOpponent($freshGame, $move, $user);

        if (! $user->isGuest()) {
            app(UpdateMoveStatsAction::class)->execute($user, $move, $freshGame, $scoringResult);

            $unlockedAchievements = app(CheckMoveAchievementsAction::class)
                ->execute($user, $move, $freshGame, $scoringResult);

            $move->setRelation('unlockedAchievements', $unlockedAchievements);
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

    private function validateMoveRules(Game $game, array $tiles, RuleEngine $ruleEngine): void
    {
        $moveData = MoveData::fromArray($tiles);
        $failures = $ruleEngine->validateMove($game, $moveData, $game->board_state);

        if ($failures->isNotEmpty()) {
            throw new InvalidMoveException($failures->first()->message);
        }
    }

    private function updatePlayerScore(GamePlayer $gamePlayer, int $score): void
    {
        $gamePlayer->addScore($score);
    }

    private function refillPlayerRack(Game $game, GamePlayer $gamePlayer, array $tiles): TileBag
    {
        $tileBag = TileBag::fromArray($game->tile_bag);

        $drawnTiles = $tileBag->draw(count($tiles));
        $drawnTiles = $this->maybeGiveBlank($drawnTiles, $gamePlayer, $tileBag);

        $newRack = collect($gamePlayer->removeTilesFromRack($tiles))
            ->merge(TileBag::tilesToArray($drawnTiles))
            ->all();

        $gamePlayer->setRackTiles($newRack);

        return $tileBag;
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

    private function createMoveRecord(Game $game, User $user, array $tiles, array $words, int $score): Move
    {
        return Move::create([
            'game_id' => $game->id,
            'user_id' => $user->id,
            'tiles' => $tiles,
            'words' => collect($words)->pluck('word')->all(),
            'score' => $score,
            'type' => MoveType::Play,
        ]);
    }

    private function updateGameState(Game $game, array $newBoard, TileBag $tileBag): void
    {
        $game->update([
            'board_state' => $newBoard,
            'tile_bag' => $tileBag->toArray(),
            'consecutive_passes' => 0,
        ]);
    }

    private function recordWordsPlayed(array $words, string $language): void
    {
        $wordStrings = collect($words)->pluck('word')->all();

        Dictionary::recordPlays($wordStrings, $language);
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
