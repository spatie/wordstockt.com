<?php

use App\Domain\Game\Enums\GameAction;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Support\Rules\EndGame\EndGameRule;
use App\Domain\Game\Support\Rules\Game\GameRule;
use App\Domain\Game\Support\Rules\RuleEngine;
use App\Domain\Game\Support\Rules\RuleResult;
use App\Domain\Game\Support\Rules\Turn\TurnRule;
use App\Domain\User\Models\User;

beforeEach(function (): void {
    $this->engine = new RuleEngine;
});

it('adds and retrieves turn rules', function (): void {
    $rule = Mockery::mock(TurnRule::class);

    $this->engine->addTurnRule($rule);

    expect($this->engine->getTurnRules())->toHaveCount(1)
        ->and($this->engine->getTurnRules()[0])->toBe($rule);
});

it('returns self for chaining when adding turn rules', function (): void {
    $rule = Mockery::mock(TurnRule::class);

    $result = $this->engine->addTurnRule($rule);

    expect($result)->toBe($this->engine);
});

it('adds and retrieves game rules', function (): void {
    $rule = Mockery::mock(GameRule::class);

    $this->engine->addGameRule($rule);

    expect($this->engine->getGameRules())->toHaveCount(1)
        ->and($this->engine->getGameRules()[0])->toBe($rule);
});

it('adds and retrieves end game rules', function (): void {
    $rule = Mockery::mock(EndGameRule::class);

    $this->engine->addEndGameRule($rule);

    expect($this->engine->getEndGameRules())->toHaveCount(1)
        ->and($this->engine->getEndGameRules()[0])->toBe($rule);
});

it('returns empty array when all rules pass on validateMove', function (): void {
    $game = Mockery::mock(Game::class);
    $move = createMove([['letter' => 'A', 'x' => 7, 'y' => 7]]);
    $board = [];

    $rule = Mockery::mock(TurnRule::class);
    $rule->shouldReceive('isEnabled')->andReturn(true);
    $rule->shouldReceive('validate')->andReturn(RuleResult::pass('test'));

    $this->engine->addTurnRule($rule);

    $failures = $this->engine->validateMove($game, $move, $board);

    expect($failures)->toBeEmpty();
});

it('returns failures when rules fail on validateMove', function (): void {
    $game = Mockery::mock(Game::class);
    $move = createMove([['letter' => 'A', 'x' => 7, 'y' => 7]]);
    $board = [];

    $rule = Mockery::mock(TurnRule::class);
    $rule->shouldReceive('isEnabled')->andReturn(true);
    $rule->shouldReceive('validate')->andReturn(RuleResult::fail('test', 'Error'));

    $this->engine->addTurnRule($rule);

    $failures = $this->engine->validateMove($game, $move, $board);

    expect($failures)->toHaveCount(1)
        ->and($failures[0]->message)->toBe('Error');
});

it('skips disabled rules on validateMove', function (): void {
    $game = Mockery::mock(Game::class);
    $move = createMove([['letter' => 'A', 'x' => 7, 'y' => 7]]);
    $board = [];

    $rule = Mockery::mock(TurnRule::class);
    $rule->shouldReceive('isEnabled')->andReturn(false);
    $rule->shouldNotReceive('validate');

    $this->engine->addTurnRule($rule);

    $failures = $this->engine->validateMove($game, $move, $board);

    expect($failures)->toBeEmpty();
});

it('collects multiple failures on validateMove', function (): void {
    $game = Mockery::mock(Game::class);
    $move = createMove([['letter' => 'A', 'x' => 7, 'y' => 7]]);
    $board = [];

    $rule1 = Mockery::mock(TurnRule::class);
    $rule1->shouldReceive('isEnabled')->andReturn(true);
    $rule1->shouldReceive('validate')->andReturn(RuleResult::fail('rule1', 'Error 1'));

    $rule2 = Mockery::mock(TurnRule::class);
    $rule2->shouldReceive('isEnabled')->andReturn(true);
    $rule2->shouldReceive('validate')->andReturn(RuleResult::fail('rule2', 'Error 2'));

    $this->engine->addTurnRule($rule1);
    $this->engine->addTurnRule($rule2);

    $failures = $this->engine->validateMove($game, $move, $board);

    expect($failures)->toHaveCount(2);
});

it('validates game rules and returns empty array on success', function (): void {
    $game = Mockery::mock(Game::class);
    $user = Mockery::mock(User::class);

    $rule = Mockery::mock(GameRule::class);
    $rule->shouldReceive('isEnabled')->andReturn(true);
    $rule->shouldReceive('isActionAllowed')->andReturn(RuleResult::pass('test'));

    $this->engine->addGameRule($rule);

    $failures = $this->engine->validateAction($game, $user, GameAction::Play);

    expect($failures)->toBeEmpty();
});

it('returns failures when game rules fail on validateAction', function (): void {
    $game = Mockery::mock(Game::class);
    $user = Mockery::mock(User::class);

    $rule = Mockery::mock(GameRule::class);
    $rule->shouldReceive('isEnabled')->andReturn(true);
    $rule->shouldReceive('isActionAllowed')->andReturn(RuleResult::fail('test', 'Not allowed'));

    $this->engine->addGameRule($rule);

    $failures = $this->engine->validateAction($game, $user, GameAction::Play);

    expect($failures)->toHaveCount(1)
        ->and($failures[0]->message)->toBe('Not allowed');
});

it('skips disabled game rules on validateAction', function (): void {
    $game = Mockery::mock(Game::class);
    $user = Mockery::mock(User::class);

    $rule = Mockery::mock(GameRule::class);
    $rule->shouldReceive('isEnabled')->andReturn(false);
    $rule->shouldNotReceive('isActionAllowed');

    $this->engine->addGameRule($rule);

    $failures = $this->engine->validateAction($game, $user, GameAction::Play);

    expect($failures)->toBeEmpty();
});

it('returns rule when game should end on checkEndGame', function (): void {
    $game = Mockery::mock(Game::class);

    $rule = Mockery::mock(EndGameRule::class);
    $rule->shouldReceive('isEnabled')->andReturn(true);
    $rule->shouldReceive('shouldEndGame')->andReturn(true);

    $this->engine->addEndGameRule($rule);

    $result = $this->engine->checkEndGame($game);

    expect($result)->toBe($rule);
});

it('returns null when game should not end on checkEndGame', function (): void {
    $game = Mockery::mock(Game::class);

    $rule = Mockery::mock(EndGameRule::class);
    $rule->shouldReceive('isEnabled')->andReturn(true);
    $rule->shouldReceive('shouldEndGame')->andReturn(false);

    $this->engine->addEndGameRule($rule);

    $result = $this->engine->checkEndGame($game);

    expect($result)->toBeNull();
});

it('skips disabled end game rules on checkEndGame', function (): void {
    $game = Mockery::mock(Game::class);

    $rule = Mockery::mock(EndGameRule::class);
    $rule->shouldReceive('isEnabled')->andReturn(false);
    $rule->shouldNotReceive('shouldEndGame');

    $this->engine->addEndGameRule($rule);

    $result = $this->engine->checkEndGame($game);

    expect($result)->toBeNull();
});

it('returns first matching rule on checkEndGame', function (): void {
    $game = Mockery::mock(Game::class);

    $rule1 = Mockery::mock(EndGameRule::class);
    $rule1->shouldReceive('isEnabled')->andReturn(true);
    $rule1->shouldReceive('shouldEndGame')->andReturn(true);

    $rule2 = Mockery::mock(EndGameRule::class);
    $rule2->shouldReceive('isEnabled')->andReturn(true);
    // Should not be called because rule1 already matched

    $this->engine->addEndGameRule($rule1);
    $this->engine->addEndGameRule($rule2);

    $result = $this->engine->checkEndGame($game);

    expect($result)->toBe($rule1);
});
