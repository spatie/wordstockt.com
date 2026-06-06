<?php

use App\Domain\Game\Actions\CreateGameAction;
use App\Domain\User\Models\User;

it('creates a game with the requested seat count', function (): void {
    $creator = User::factory()->create();

    $game = app(CreateGameAction::class)->execute($creator, 'en', maxPlayers: 4);

    expect($game->max_players)->toBe(4)
        ->and($game->gamePlayers()->count())->toBe(1);
});

it('defaults to two seats', function (): void {
    $game = app(CreateGameAction::class)->execute(User::factory()->create(), 'en');

    expect($game->max_players)->toBe(2);
});
