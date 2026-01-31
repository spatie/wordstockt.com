<?php

use App\Domain\Game\Enums\GameStatus;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\GamePlayer;
use App\Domain\User\Models\User;

it('allows user to join pending game', function (): void {
    $creator = User::factory()->create();
    $joiner = User::factory()->create();

    // Create a pending game (single player) with tile bag
    $game = Game::factory()->create([
        'status' => GameStatus::Pending,
        'language' => 'en',
        'tile_bag' => createDefaultTileBag(),
    ]);
    GamePlayer::factory()->create([
        'game_id' => $game->id,
        'user_id' => $creator->id,
        'rack_tiles' => createDefaultRack(),
        'score' => 0,
        'turn_order' => 1,
    ]);

    $response = $this->actingAs($joiner, 'sanctum')
        ->postJson("/api/games/{$game->ulid}/join");

    $response->assertOk()
        ->assertJsonPath('data.status', GameStatus::Active->value);
});

it('adds joiner as second player', function (): void {
    $creator = User::factory()->create();
    $joiner = User::factory()->create();

    $game = Game::factory()->create([
        'status' => GameStatus::Pending,
        'tile_bag' => createDefaultTileBag(),
    ]);
    GamePlayer::factory()->create([
        'game_id' => $game->id,
        'user_id' => $creator->id,
        'rack_tiles' => createDefaultRack(),
        'score' => 0,
        'turn_order' => 1,
    ]);

    $this->actingAs($joiner, 'sanctum')
        ->postJson("/api/games/{$game->ulid}/join");

    $game->refresh();
    expect($game->players)->toHaveCount(2)
        ->and($game->hasPlayer($joiner))->toBeTrue();
});

it('fails to join active game', function (): void {
    $user = User::factory()->create();
    $game = createGameWithPlayers(status: GameStatus::Active);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/games/{$game->ulid}/join");

    $response->assertStatus(403);
});

it('fails to join finished game', function (): void {
    $user = User::factory()->create();
    $game = createGameWithPlayers(status: GameStatus::Finished);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/games/{$game->ulid}/join");

    $response->assertStatus(403);
});

it('fails to join own game', function (): void {
    $user = User::factory()->create();

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
        ->postJson("/api/games/{$game->ulid}/join");

    $response->assertStatus(403);
});

it('returns 401 for unauthenticated request', function (): void {
    $game = Game::factory()->create([
        'status' => GameStatus::Pending,
    ]);

    $response = $this->postJson("/api/games/{$game->ulid}/join");

    $response->assertStatus(401);
});
