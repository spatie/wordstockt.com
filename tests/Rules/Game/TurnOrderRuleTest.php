<?php

use App\Domain\Game\Enums\GameAction;
use App\Domain\Game\Support\Rules\Game\TurnOrderRule;
use App\Domain\User\Models\User;

beforeEach(function (): void {
    $this->rule = new TurnOrderRule;
});

it('has correct identifier', function (): void {
    expect($this->rule->getIdentifier())->toBe('game.turn_order');
});

it('has correct name', function (): void {
    expect($this->rule->getName())->toBe('Turn Order');
});

it('passes when it is the player turn', function (): void {
    $game = createGameWithPlayers();
    $currentPlayer = User::find($game->current_turn_user_id);

    $result = $this->rule->isActionAllowed($game, $currentPlayer, GameAction::Play);

    expect($result->passed)->toBeTrue();
});

it('fails when it is not the player turn', function (): void {
    $game = createGameWithPlayers();
    $otherPlayer = $game->players->firstWhere('id', '!=', $game->current_turn_user_id);

    $result = $this->rule->isActionAllowed($game, $otherPlayer, GameAction::Play);

    expect($result->passed)->toBeFalse()
        ->and($result->message)->toBe('It is not your turn.');
});

it('allows resign even when not player turn', function (): void {
    $game = createGameWithPlayers();
    $otherPlayer = $game->players->firstWhere('id', '!=', $game->current_turn_user_id);

    $result = $this->rule->isActionAllowed($game, $otherPlayer, GameAction::Resign);

    expect($result->passed)->toBeTrue();
});

it('fails for pass when not player turn', function (): void {
    $game = createGameWithPlayers();
    $otherPlayer = $game->players->firstWhere('id', '!=', $game->current_turn_user_id);

    $result = $this->rule->isActionAllowed($game, $otherPlayer, GameAction::Pass);

    expect($result->passed)->toBeFalse();
});

it('fails for swap when not player turn', function (): void {
    $game = createGameWithPlayers();
    $otherPlayer = $game->players->firstWhere('id', '!=', $game->current_turn_user_id);

    $result = $this->rule->isActionAllowed($game, $otherPlayer, GameAction::Swap);

    expect($result->passed)->toBeFalse();
});
