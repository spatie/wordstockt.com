<?php

use App\Domain\Game\Enums\GameAction;
use App\Domain\Game\Enums\GameStatus;
use App\Domain\Game\Support\Rules\Game\GameActiveRule;

beforeEach(function (): void {
    $this->rule = new GameActiveRule;
});

it('has correct identifier', function (): void {
    expect($this->rule->getIdentifier())->toBe('game.game_active');
});

it('has correct name', function (): void {
    expect($this->rule->getName())->toBe('Game Active');
});

it('is enabled by default', function (): void {
    expect($this->rule->isEnabled())->toBeTrue();
});

it('passes when game is active', function (): void {
    $game = createGameWithPlayers(status: GameStatus::Active);
    $user = $game->players->first();

    $result = $this->rule->isActionAllowed($game, $user, GameAction::Play);

    expect($result->passed)->toBeTrue();
});

it('fails when game is pending', function (): void {
    $game = createGameWithPlayers(status: GameStatus::Pending);
    $user = $game->players->first();

    $result = $this->rule->isActionAllowed($game, $user, GameAction::Play);

    expect($result->passed)->toBeFalse()
        ->and($result->message)->toBe('Game is not active.');
});

it('fails when game is finished', function (): void {
    $game = createGameWithPlayers(status: GameStatus::Finished);
    $user = $game->players->first();

    $result = $this->rule->isActionAllowed($game, $user, GameAction::Play);

    expect($result->passed)->toBeFalse();
});

it('fails for any action when game is not active', function (): void {
    $game = createGameWithPlayers(status: GameStatus::Pending);
    $user = $game->players->first();

    $playResult = $this->rule->isActionAllowed($game, $user, GameAction::Play);
    $passResult = $this->rule->isActionAllowed($game, $user, GameAction::Pass);
    $swapResult = $this->rule->isActionAllowed($game, $user, GameAction::Swap);

    expect($playResult->passed)->toBeFalse()
        ->and($passResult->passed)->toBeFalse()
        ->and($swapResult->passed)->toBeFalse();
});
