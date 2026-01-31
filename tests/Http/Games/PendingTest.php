<?php

use App\Domain\Game\Enums\GameStatus;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\GamePlayer;
use App\Domain\User\Models\User;

it('returns pending games user can join', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    // Create a pending game by another user
    $game = Game::factory()->create([
        'status' => GameStatus::Pending,
        'tile_bag' => createDefaultTileBag(),
    ]);
    GamePlayer::factory()->create([
        'game_id' => $game->id,
        'user_id' => $otherUser->id,
        'rack_tiles' => createDefaultRack(),
        'score' => 0,
        'turn_order' => 1,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/games/pending');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => ['ulid', 'language', 'creator', 'created_at'],
            ],
        ]);

    expect($response->json('data'))->toHaveCount(1);
});

it('excludes pending games user created', function (): void {
    $user = User::factory()->create();

    // Create a pending game by this user
    $game = Game::factory()->create([
        'status' => GameStatus::Pending,
        'tile_bag' => createDefaultTileBag(),
    ]);
    GamePlayer::factory()->create([
        'game_id' => $game->id,
        'user_id' => $user->id,
        'rack_tiles' => createDefaultRack(),
        'score' => 0,
        'turn_order' => 1,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/games/pending');

    $response->assertOk();
    expect($response->json('data'))->toBeEmpty();
});

it('excludes active games', function (): void {
    $user = User::factory()->create();
    createGameWithPlayers(status: GameStatus::Active);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/games/pending');

    $response->assertOk();
    expect($response->json('data'))->toBeEmpty();
});

it('excludes finished games', function (): void {
    $user = User::factory()->create();
    createGameWithPlayers(status: GameStatus::Finished);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/games/pending');

    $response->assertOk();
    expect($response->json('data'))->toBeEmpty();
});

it('limits to 20 games', function (): void {
    $user = User::factory()->create();

    // Create 25 pending games by other users
    for ($i = 0; $i < 25; $i++) {
        $otherUser = User::factory()->create();
        $game = Game::factory()->create([
            'status' => GameStatus::Pending,
            'tile_bag' => createDefaultTileBag(),
        ]);
        GamePlayer::factory()->create([
            'game_id' => $game->id,
            'user_id' => $otherUser->id,
            'rack_tiles' => createDefaultRack(),
            'score' => 0,
            'turn_order' => 1,
        ]);
    }

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/games/pending');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(20);
});

it('returns 401 for unauthenticated request', function (): void {
    $response = $this->getJson('/api/games/pending');

    $response->assertStatus(401);
});
