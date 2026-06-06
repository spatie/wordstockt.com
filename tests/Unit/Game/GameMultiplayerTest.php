<?php

use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\GamePlayer;
use App\Domain\User\Models\User;

it('has room for more players until max_players is reached', function (): void {
    $game = Game::factory()->pending()->create(['max_players' => 3]);
    GamePlayer::factory()->for($game)->count(2)->create();

    expect($game->fresh()->hasRoomForMorePlayers())->toBeTrue();

    GamePlayer::factory()->for($game)->create();

    expect($game->fresh()->hasRoomForMorePlayers())->toBeFalse();
});

it('identifies multiplayer games by seat count', function (): void {
    expect(Game::factory()->create(['max_players' => 2])->isMultiplayer())->toBeFalse()
        ->and(Game::factory()->create(['max_players' => 3])->isMultiplayer())->toBeTrue();
});

it('lists other active players excluding self and left players', function (): void {
    $game = Game::factory()->create(['max_players' => 3]);
    $me = User::factory()->create();
    $other = User::factory()->create();
    $gone = User::factory()->create();
    GamePlayer::factory()->for($game)->create(['user_id' => $me->id]);
    GamePlayer::factory()->for($game)->create(['user_id' => $other->id]);
    GamePlayer::factory()->for($game)->left()->create(['user_id' => $gone->id]);

    $others = $game->fresh()->otherActivePlayers($me);

    expect($others->pluck('id')->all())->toBe([$other->id]);
});
