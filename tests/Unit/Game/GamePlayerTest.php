<?php

use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\GamePlayer;

it('reports whether a player has left', function (): void {
    $active = GamePlayer::factory()->create();
    $gone = GamePlayer::factory()->left('removed')->create();

    expect($active->hasLeft())->toBeFalse()
        ->and($gone->hasLeft())->toBeTrue();
});

it('scopes to active players only', function (): void {
    $game = Game::factory()->create();
    GamePlayer::factory()->for($game)->count(2)->create();
    GamePlayer::factory()->for($game)->left()->create();

    expect($game->gamePlayers()->active()->count())->toBe(2);
});
