<?php

use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\GamePlayer;
use App\Domain\Game\Support\Board;
use App\Domain\Game\Support\Scoring\Rules\EndGameBonusRule;
use App\Domain\Game\Support\Scoring\ScoringContext;
use App\Domain\Game\Support\Scoring\ScoringResult;

beforeEach(function (): void {
    $this->rule = new EndGameBonusRule;
});

function createEndGameContext(bool $isEndGame, bool $clearedRack, array $tileBag = []): ScoringContext
{
    $game = Game::factory()->create(['tile_bag' => $tileBag]);
    $gamePlayer = GamePlayer::factory()->create(['game_id' => $game->id]);

    return new ScoringContext(
        game: $game,
        words: [],
        placedTiles: [],
        placedPositions: [],
        board: new Board,
        gamePlayer: $gamePlayer,
        isEndGame: $isEndGame,
        playerClearedRack: $clearedRack,
    );
}

it('has correct identifier', function (): void {
    expect($this->rule->getIdentifier())->toBe('scoring.end_game_bonus');
});

it('has correct name', function (): void {
    expect($this->rule->getName())->toBe('End Game Bonus');
});

it('is enabled by default', function (): void {
    expect($this->rule->isEnabled())->toBeTrue();
});

it('adds 25 points when player cleared rack and bag is empty', function (): void {
    $context = createEndGameContext(isEndGame: true, clearedRack: true, tileBag: []);

    $result = $this->rule->apply($context, ScoringResult::empty());

    expect($result->getBonusTotal())->toBe(25)
        ->and($result->hasBonus('scoring.end_game_bonus'))->toBeTrue();
});

it('does not add bonus when not end game', function (): void {
    $context = createEndGameContext(isEndGame: false, clearedRack: true, tileBag: []);

    $result = $this->rule->apply($context, ScoringResult::empty());

    expect($result->getBonusTotal())->toBe(0);
});

it('does not add bonus when player did not clear rack', function (): void {
    $context = createEndGameContext(isEndGame: true, clearedRack: false, tileBag: []);

    $result = $this->rule->apply($context, ScoringResult::empty());

    expect($result->getBonusTotal())->toBe(0);
});

it('does not add bonus when bag is not empty', function (): void {
    $context = createEndGameContext(
        isEndGame: true,
        clearedRack: true,
        tileBag: [['letter' => 'A', 'points' => 1]],
    );

    $result = $this->rule->apply($context, ScoringResult::empty());

    expect($result->getBonusTotal())->toBe(0);
});

it('does not add bonus during regular move scoring', function (): void {
    $context = createScoringContextWithWord('HELLO');

    $result = $this->rule->apply($context, ScoringResult::empty());

    expect($result->getBonusTotal())->toBe(0);
});

it('adds correct bonus description', function (): void {
    $context = createEndGameContext(isEndGame: true, clearedRack: true, tileBag: []);

    $result = $this->rule->apply($context, ScoringResult::empty());
    $bonuses = $result->getBonuses();

    expect($bonuses)->toHaveCount(1)
        ->and($bonuses->first()['description'])->toBe('First to clear rack when bag is empty');
});
