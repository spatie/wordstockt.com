<?php

use App\Domain\Game\Actions\CreateGameAction;
use App\Domain\Game\Enums\GameStatus;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\GamePlayer;
use App\Domain\User\Models\User;

it('returns public pending games', function (): void {
    $creator = User::factory()->create();
    $viewer = User::factory()->create();

    $game = Game::factory()->create([
        'status' => GameStatus::Pending,
        'is_public' => true,
        'tile_bag' => createDefaultTileBag(),
    ]);
    GamePlayer::factory()->create([
        'game_id' => $game->id,
        'user_id' => $creator->id,
        'rack_tiles' => createDefaultRack(),
        'turn_order' => 1,
    ]);

    $response = $this->actingAs($viewer, 'sanctum')
        ->getJson('/api/games/public');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.ulid', $game->ulid);
});

it('excludes private games from public list', function (): void {
    $creator = User::factory()->create();
    $viewer = User::factory()->create();

    $game = Game::factory()->create([
        'status' => GameStatus::Pending,
        'is_public' => false,
        'tile_bag' => createDefaultTileBag(),
    ]);
    GamePlayer::factory()->create([
        'game_id' => $game->id,
        'user_id' => $creator->id,
        'rack_tiles' => createDefaultRack(),
        'turn_order' => 1,
    ]);

    $response = $this->actingAs($viewer, 'sanctum')
        ->getJson('/api/games/public');

    $response->assertOk()
        ->assertJsonCount(0, 'data');
});

it('excludes games user created from public list', function (): void {
    $creator = User::factory()->create();

    $game = Game::factory()->create([
        'status' => GameStatus::Pending,
        'is_public' => true,
        'tile_bag' => createDefaultTileBag(),
    ]);
    GamePlayer::factory()->create([
        'game_id' => $game->id,
        'user_id' => $creator->id,
        'rack_tiles' => createDefaultRack(),
        'turn_order' => 1,
    ]);

    $response = $this->actingAs($creator, 'sanctum')
        ->getJson('/api/games/public');

    $response->assertOk()
        ->assertJsonCount(0, 'data');
});

it('excludes active games from public list', function (): void {
    $viewer = User::factory()->create();

    $game = Game::factory()->create([
        'status' => GameStatus::Active,
        'is_public' => true,
    ]);

    $response = $this->actingAs($viewer, 'sanctum')
        ->getJson('/api/games/public');

    $response->assertOk()
        ->assertJsonCount(0, 'data');
});

it('limits public games to 100', function (): void {
    $creator = User::factory()->create();
    $viewer = User::factory()->create();

    for ($i = 0; $i < 105; $i++) {
        $game = Game::factory()->create([
            'status' => GameStatus::Pending,
            'is_public' => true,
            'tile_bag' => createDefaultTileBag(),
        ]);
        GamePlayer::factory()->create([
            'game_id' => $game->id,
            'user_id' => $creator->id,
            'rack_tiles' => createDefaultRack(),
            'turn_order' => 1,
        ]);
    }

    $response = $this->actingAs($viewer, 'sanctum')
        ->getJson('/api/games/public');

    $response->assertOk()
        ->assertJsonCount(100, 'data');
});

it('includes board_template in public game response', function (): void {
    $creator = User::factory()->create();
    $viewer = User::factory()->create();

    $customTemplate = array_fill(0, 15, array_fill(0, 15, null));
    $customTemplate[7][7] = 'STAR';

    $game = Game::factory()->create([
        'status' => GameStatus::Pending,
        'is_public' => true,
        'board_template' => $customTemplate,
        'tile_bag' => createDefaultTileBag(),
    ]);
    GamePlayer::factory()->create([
        'game_id' => $game->id,
        'user_id' => $creator->id,
        'rack_tiles' => createDefaultRack(),
        'turn_order' => 1,
    ]);

    $response = $this->actingAs($viewer, 'sanctum')
        ->getJson('/api/games/public');

    $response->assertOk()
        ->assertJsonPath('data.0.board_template.7.7', 'STAR');
});

it('creates a public game', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/games', [
            'language' => 'en',
            'is_public' => true,
        ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.is_public', true);
});

it('creates a private game by default', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/games', [
            'language' => 'en',
        ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.is_public', false);
});

it('allows viewing public pending game as non-player', function (): void {
    $creator = User::factory()->create();
    $viewer = User::factory()->create();

    $game = Game::factory()->create([
        'status' => GameStatus::Pending,
        'is_public' => true,
        'tile_bag' => createDefaultTileBag(),
    ]);
    GamePlayer::factory()->create([
        'game_id' => $game->id,
        'user_id' => $creator->id,
        'rack_tiles' => createDefaultRack(),
        'turn_order' => 1,
    ]);

    $response = $this->actingAs($viewer, 'sanctum')
        ->getJson("/api/games/{$game->ulid}");

    $response->assertOk()
        ->assertJsonPath('data.can_join', true);
});

it('denies viewing private game as non-player', function (): void {
    $creator = User::factory()->create();
    $viewer = User::factory()->create();

    $game = Game::factory()->create([
        'status' => GameStatus::Pending,
        'is_public' => false,
        'tile_bag' => createDefaultTileBag(),
    ]);
    GamePlayer::factory()->create([
        'game_id' => $game->id,
        'user_id' => $creator->id,
        'rack_tiles' => createDefaultRack(),
        'turn_order' => 1,
    ]);

    $response = $this->actingAs($viewer, 'sanctum')
        ->getJson("/api/games/{$game->ulid}");

    $response->assertForbidden();
});

it('returns 401 for unauthenticated request', function (): void {
    $response = $this->getJson('/api/games/public');

    $response->assertStatus(401);
});

it('prevents creating public game when user has 10 pending public games', function (): void {
    $user = User::factory()->create();

    for ($i = 0; $i < CreateGameAction::MAX_PENDING_PUBLIC_GAMES; $i++) {
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
    }

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/games', [
            'language' => 'en',
            'is_public' => true,
        ]);

    $response->assertStatus(422)
        ->assertJsonPath('message', 'You already have 10 pending public games. Please wait for someone to join or delete some games.');
});

it('allows creating private game when user has 10 pending public games', function (): void {
    $user = User::factory()->create();

    for ($i = 0; $i < CreateGameAction::MAX_PENDING_PUBLIC_GAMES; $i++) {
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
    }

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/games', [
            'language' => 'en',
            'is_public' => false,
        ]);

    $response->assertStatus(201);
});

it('allows creating public game when user has 9 pending public games', function (): void {
    $user = User::factory()->create();

    for ($i = 0; $i < CreateGameAction::MAX_PENDING_PUBLIC_GAMES - 1; $i++) {
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
    }

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/games', [
            'language' => 'en',
            'is_public' => true,
        ]);

    $response->assertStatus(201);
});

it('does not count public games with opponent toward limit', function (): void {
    $user = User::factory()->create();
    $opponent = User::factory()->create();

    for ($i = 0; $i < CreateGameAction::MAX_PENDING_PUBLIC_GAMES; $i++) {
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
        GamePlayer::factory()->create([
            'game_id' => $game->id,
            'user_id' => $opponent->id,
            'rack_tiles' => createDefaultRack(),
            'turn_order' => 2,
        ]);
    }

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/games', [
            'language' => 'en',
            'is_public' => true,
        ]);

    $response->assertStatus(201);
});

it('does not count active public games toward limit', function (): void {
    $user = User::factory()->create();

    for ($i = 0; $i < CreateGameAction::MAX_PENDING_PUBLIC_GAMES; $i++) {
        $game = Game::factory()->create([
            'status' => GameStatus::Active,
            'is_public' => true,
            'tile_bag' => createDefaultTileBag(),
        ]);
        GamePlayer::factory()->create([
            'game_id' => $game->id,
            'user_id' => $user->id,
            'rack_tiles' => createDefaultRack(),
            'turn_order' => 1,
        ]);
    }

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/games', [
            'language' => 'en',
            'is_public' => true,
        ]);

    $response->assertStatus(201);
});

it('does not count private pending games toward limit', function (): void {
    $user = User::factory()->create();

    for ($i = 0; $i < CreateGameAction::MAX_PENDING_PUBLIC_GAMES; $i++) {
        $game = Game::factory()->create([
            'status' => GameStatus::Pending,
            'is_public' => false,
            'tile_bag' => createDefaultTileBag(),
        ]);
        GamePlayer::factory()->create([
            'game_id' => $game->id,
            'user_id' => $user->id,
            'rack_tiles' => createDefaultRack(),
            'turn_order' => 1,
        ]);
    }

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/games', [
            'language' => 'en',
            'is_public' => true,
        ]);

    $response->assertStatus(201);
});
