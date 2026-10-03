<?php

use App\Domain\Game\Enums\GameStatus;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\GamePlayer;
use App\Domain\User\Actions\DeleteUserAction;
use App\Domain\User\Models\User;

it('deletes pending games nobody else has joined', function (): void {
    $user = User::factory()->create();

    $game = Game::factory()->create([
        'status' => GameStatus::Pending,
        'is_public' => true,
        'tile_bag' => createDefaultTileBag(),
    ]);
    GamePlayer::factory()->create([
        'game_id' => $game->id,
        'user_id' => $user->id,
        'rack_tiles' => createDefaultRack(),
        'turn_order' => 1,
    ]);

    app(DeleteUserAction::class)->execute($user);

    expect(Game::find($game->id))->toBeNull();
});

it('keeps games other players have joined', function (): void {
    $user = User::factory()->create();
    $opponent = User::factory()->create();

    $game = Game::factory()->create([
        'status' => GameStatus::Active,
        'tile_bag' => createDefaultTileBag(),
    ]);
    GamePlayer::factory()->create([
        'game_id' => $game->id,
        'user_id' => $user->id,
        'rack_tiles' => createDefaultRack(),
        'turn_order' => 1,
    ]);
    GamePlayer::factory()->create([
        'game_id' => $game->id,
        'user_id' => $opponent->id,
        'rack_tiles' => createDefaultRack(),
        'turn_order' => 2,
    ]);

    app(DeleteUserAction::class)->execute($user);

    expect(Game::find($game->id))->not->toBeNull();
});
