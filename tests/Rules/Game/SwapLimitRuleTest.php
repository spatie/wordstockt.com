<?php

use App\Domain\Game\Enums\GameAction;
use App\Domain\Game\Support\Rules\Game\SwapLimitRule;

beforeEach(function (): void {
    $this->rule = new SwapLimitRule;
});

it('has correct identifier', function (): void {
    expect($this->rule->getIdentifier())->toBe('game.swap_limit');
});

it('has correct name', function (): void {
    expect($this->rule->getName())->toBe('Swap Limit');
});

it('passes for non-swap actions regardless of bag size', function (): void {
    $game = createGameWithPlayers();
    $game->update(['tile_bag' => []]);
    $user = $game->players->first();

    $playResult = $this->rule->isActionAllowed($game, $user, GameAction::Play);
    $passResult = $this->rule->isActionAllowed($game, $user, GameAction::Pass);
    $resignResult = $this->rule->isActionAllowed($game, $user, GameAction::Resign);

    expect($playResult->passed)->toBeTrue()
        ->and($passResult->passed)->toBeTrue()
        ->and($resignResult->passed)->toBeTrue();
});

it('passes for swap when bag has exactly 7 tiles', function (): void {
    $game = createGameWithPlayers();
    $game->update(['tile_bag' => array_fill(0, 7, ['letter' => 'A', 'points' => 1])]);
    $user = $game->players->first();

    $result = $this->rule->isActionAllowed($game, $user, GameAction::Swap);

    expect($result->passed)->toBeTrue();
});

it('passes for swap when bag has more than 7 tiles', function (): void {
    $game = createGameWithPlayers();
    $game->update(['tile_bag' => array_fill(0, 50, ['letter' => 'A', 'points' => 1])]);
    $user = $game->players->first();

    $result = $this->rule->isActionAllowed($game, $user, GameAction::Swap);

    expect($result->passed)->toBeTrue();
});

it('fails for swap when bag has less than 7 tiles', function (): void {
    $game = createGameWithPlayers();
    $game->update(['tile_bag' => array_fill(0, 6, ['letter' => 'A', 'points' => 1])]);
    $user = $game->players->first();

    $result = $this->rule->isActionAllowed($game, $user, GameAction::Swap);

    expect($result->passed)->toBeFalse()
        ->and($result->message)->toContain('Not enough tiles');
});

it('fails for swap when bag is empty', function (): void {
    $game = createGameWithPlayers();
    $game->update(['tile_bag' => []]);
    $user = $game->players->first();

    $result = $this->rule->isActionAllowed($game, $user, GameAction::Swap);

    expect($result->passed)->toBeFalse();
});
