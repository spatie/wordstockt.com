<?php

use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\GamePlayer;
use App\Domain\Game\Support\Board;
use App\Domain\Game\Support\Scoring\Rules\BingoBonusRule;
use App\Domain\Game\Support\Scoring\Rules\EndGameBonusRule;
use App\Domain\Game\Support\Scoring\Rules\LetterScoreRule;
use App\Domain\Game\Support\Scoring\Rules\ScoringRule;
use App\Domain\Game\Support\Scoring\Rules\WordLengthBonusRule;
use App\Domain\Game\Support\Scoring\ScoringEngine;

it('adds and retrieves rules', function (): void {
    $engine = new ScoringEngine;
    $rule = new LetterScoreRule;

    $engine->addRule($rule);

    expect($engine->getRules())->toHaveCount(1)
        ->and($engine->getRules()->first())->toBe($rule);
});

it('returns self for chaining when adding rules', function (): void {
    $engine = new ScoringEngine;

    $result = $engine->addRule(new LetterScoreRule);

    expect($result)->toBe($engine);
});

it('can add multiple rules', function (): void {
    $engine = (new ScoringEngine)
        ->addRule(new LetterScoreRule)
        ->addRule(new BingoBonusRule)
        ->addRule(new WordLengthBonusRule);

    expect($engine->getRules())->toHaveCount(3);
});

it('applies all rules in order when calculating move score', function (): void {
    $engine = (new ScoringEngine)
        ->addRule(new LetterScoreRule)
        ->addRule(new BingoBonusRule)
        ->addRule(new WordLengthBonusRule);

    $game = Mockery::mock(Game::class);
    $game->shouldReceive('getAttribute')->with('board_template')->andReturn(null);
    $board = new Board;

    // 7-letter word starting at (7,7) - DW at center
    // DL at (11,7) - position (7,11) in y,x notation
    $tiles = [];
    for ($i = 0; $i < 7; $i++) {
        $tiles[] = ['letter' => 'A', 'points' => 1, 'x' => 7 + $i, 'y' => 7, 'is_blank' => false];
    }
    $words = [createWordData('AAAAAAA', $tiles)];

    $result = $engine->calculateMoveScore($game, $words, $tiles, $board);

    // Letter score: 6 regular A's (6 pts) + 1 DL at (11,7) (2 pts) = 8
    // DW at (7,7) = 8 * 2 = 16
    // Bingo bonus: +50
    // Tiles played bonus: 7 tiles = +100
    expect($result->getWordsTotal())->toBe(16)
        ->and($result->getBonusTotal())->toBe(150)
        ->and($result->getTotal())->toBe(166);
});

it('skips disabled rules when calculating move score', function (): void {
    $disabledRule = Mockery::mock(ScoringRule::class);
    $disabledRule->shouldReceive('isEnabled')->andReturn(false);
    $disabledRule->shouldNotReceive('apply');

    $engine = (new ScoringEngine)
        ->addRule($disabledRule)
        ->addRule(new LetterScoreRule);

    $game = Mockery::mock(Game::class);
    $game->shouldReceive('getAttribute')->with('board_template')->andReturn(null);
    $board = new Board;
    $tiles = [['letter' => 'A', 'points' => 1, 'x' => 0, 'y' => 0, 'is_blank' => false]];
    $words = [createWordData('A', $tiles)];

    $result = $engine->calculateMoveScore($game, $words, $tiles, $board);

    expect($result->getTotal())->toBeGreaterThan(0);
});

it('returns empty result when no rules', function (): void {
    $engine = new ScoringEngine;
    $game = Mockery::mock(Game::class);
    $game->shouldReceive('getAttribute')->with('board_template')->andReturn(null);
    $board = new Board;
    $tiles = [['letter' => 'A', 'points' => 1, 'x' => 0, 'y' => 0, 'is_blank' => false]];
    $words = [createWordData('A', $tiles)];

    $result = $engine->calculateMoveScore($game, $words, $tiles, $board);

    expect($result->getTotal())->toBe(0);
});

it('calculates end game bonus when player cleared rack', function (): void {
    $engine = (new ScoringEngine)
        ->addRule(new EndGameBonusRule);

    $game = Game::factory()->create(['tile_bag' => []]);
    $gamePlayer = GamePlayer::factory()->create(['game_id' => $game->id]);

    $result = $engine->calculateEndGameScore($game, $gamePlayer, clearedRack: true);

    expect($result->getBonusTotal())->toBe(25);
});

it('returns zero end game bonus when player did not clear rack', function (): void {
    $engine = (new ScoringEngine)
        ->addRule(new EndGameBonusRule);

    $game = Game::factory()->create(['tile_bag' => []]);
    $gamePlayer = GamePlayer::factory()->create(['game_id' => $game->id]);

    $result = $engine->calculateEndGameScore($game, $gamePlayer, clearedRack: false);

    expect($result->getBonusTotal())->toBe(0);
});

it('sums rack tile points for end game penalty', function (): void {
    $engine = new ScoringEngine;

    $rackTiles = [
        ['letter' => 'Q', 'points' => 10],
        ['letter' => 'Z', 'points' => 10],
        ['letter' => 'A', 'points' => 1],
    ];

    $penalty = $engine->calculateEndGamePenalty($rackTiles);

    expect($penalty)->toBe(21);
});

it('returns zero penalty for empty rack', function (): void {
    $engine = new ScoringEngine;

    $penalty = $engine->calculateEndGamePenalty([]);

    expect($penalty)->toBe(0);
});

it('handles missing points key in rack tiles', function (): void {
    $engine = new ScoringEngine;

    $rackTiles = [
        ['letter' => 'A'],
        ['letter' => 'B', 'points' => 3],
    ];

    $penalty = $engine->calculateEndGamePenalty($rackTiles);

    expect($penalty)->toBe(3);
});

it('correctly calculates a complex move score', function (): void {
    $engine = (new ScoringEngine)
        ->addRule(new LetterScoreRule)
        ->addRule(new BingoBonusRule)
        ->addRule(new WordLengthBonusRule)
        ->addRule(new EndGameBonusRule);

    $game = Mockery::mock(Game::class);
    $game->shouldReceive('getAttribute')->with('board_template')->andReturn(null);
    $board = new Board;

    // 5-letter word "QUERY" at (0,0) - Triple Word square
    // Position (3,0) is DL - R gets doubled
    // Q=10, U=1, E=1, R=1*2(DL)=2, Y=4 = 18 * 3 (TW) = 54
    // Tiles played bonus: 5 tiles = +25
    $tiles = [
        ['letter' => 'Q', 'points' => 10, 'x' => 0, 'y' => 0, 'is_blank' => false],
        ['letter' => 'U', 'points' => 1, 'x' => 1, 'y' => 0, 'is_blank' => false],
        ['letter' => 'E', 'points' => 1, 'x' => 2, 'y' => 0, 'is_blank' => false],
        ['letter' => 'R', 'points' => 1, 'x' => 3, 'y' => 0, 'is_blank' => false],
        ['letter' => 'Y', 'points' => 4, 'x' => 4, 'y' => 0, 'is_blank' => false],
    ];
    $words = [createWordData('QUERY', $tiles)];

    $result = $engine->calculateMoveScore($game, $words, $tiles, $board);

    expect($result->getWordsTotal())->toBe(54)
        ->and($result->getBonusTotal())->toBe(25)
        ->and($result->getTotal())->toBe(79);
});

it('provides detailed score breakdown', function (): void {
    $engine = (new ScoringEngine)
        ->addRule(new LetterScoreRule)
        ->addRule(new WordLengthBonusRule);

    $game = Mockery::mock(Game::class);
    $game->shouldReceive('getAttribute')->with('board_template')->andReturn(null);
    $board = new Board;

    $tiles = [
        ['letter' => 'H', 'points' => 4, 'x' => 7, 'y' => 7, 'is_blank' => false],
        ['letter' => 'E', 'points' => 1, 'x' => 8, 'y' => 7, 'is_blank' => false],
        ['letter' => 'L', 'points' => 1, 'x' => 9, 'y' => 7, 'is_blank' => false],
        ['letter' => 'L', 'points' => 1, 'x' => 10, 'y' => 7, 'is_blank' => false],
        ['letter' => 'O', 'points' => 1, 'x' => 11, 'y' => 7, 'is_blank' => false],
    ];
    $words = [createWordData('HELLO', $tiles)];

    $result = $engine->calculateMoveScore($game, $words, $tiles, $board);
    $breakdown = $result->toArray();

    expect($breakdown)->toHaveKeys(['total', 'words_total', 'bonus_total', 'words', 'bonuses'])
        ->and($breakdown['words'])->toHaveCount(1)
        ->and($breakdown['words'][0]['word'])->toBe('HELLO')
        ->and($breakdown['bonuses'])->toHaveCount(1)
        ->and($breakdown['bonuses'][0]['rule'])->toBe('scoring.word_length_bonus');
});
