<?php

use App\Domain\User\Models\User;

it('allows swap when bag has enough tiles', function (): void {
    $user = User::factory()->create();
    $game = createGameWithPlayers(player1: $user);
    $game->update(['tile_bag' => array_fill(0, 10, ['letter' => 'X', 'points' => 8, 'is_blank' => false])]);

    $gamePlayer = $game->gamePlayers->where('user_id', $user->id)->first();
    $gamePlayer->update([
        'rack_tiles' => [
            ['letter' => 'A', 'points' => 1, 'is_blank' => false],
            ['letter' => 'B', 'points' => 3, 'is_blank' => false],
        ],
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/games/{$game->ulid}/swap", [
            'tiles' => [
                ['letter' => 'A', 'points' => 1],
            ],
        ]);

    $response->assertOk()
        ->assertJsonStructure([
            'move' => ['ulid', 'type'],
            'data',
        ]);
});

it('returns swap move type', function (): void {
    $user = User::factory()->create();
    $game = createGameWithPlayers(player1: $user);
    $game->update(['tile_bag' => array_fill(0, 10, ['letter' => 'X', 'points' => 8, 'is_blank' => false])]);

    $gamePlayer = $game->gamePlayers->where('user_id', $user->id)->first();
    $gamePlayer->update([
        'rack_tiles' => [['letter' => 'A', 'points' => 1, 'is_blank' => false]],
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/games/{$game->ulid}/swap", [
            'tiles' => [['letter' => 'A', 'points' => 1]],
        ]);

    $response->assertOk()
        ->assertJsonPath('move.type', 'swap');
});

it('rejects swap when bag has fewer than 7 tiles', function (): void {
    $user = User::factory()->create();
    $game = createGameWithPlayers(player1: $user);
    $game->update(['tile_bag' => array_fill(0, 5, ['letter' => 'X', 'points' => 8, 'is_blank' => false])]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/games/{$game->ulid}/swap", [
            'tiles' => [['letter' => 'A', 'points' => 1]],
        ]);

    $response->assertStatus(422);
});

it('rejects swap when not player turn', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $game = createGameWithPlayers(player1: $otherUser, player2: $user);
    $game->update(['tile_bag' => array_fill(0, 10, ['letter' => 'X', 'points' => 8, 'is_blank' => false])]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/games/{$game->ulid}/swap", [
            'tiles' => [['letter' => 'A', 'points' => 1]],
        ]);

    $response->assertStatus(422);
});

it('validates tiles array is required', function (): void {
    $user = User::factory()->create();
    $game = createGameWithPlayers(player1: $user);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/games/{$game->ulid}/swap", []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['tiles']);
});

it('validates max 7 tiles for swap', function (): void {
    $user = User::factory()->create();
    $game = createGameWithPlayers(player1: $user);
    $game->update(['tile_bag' => array_fill(0, 20, ['letter' => 'X', 'points' => 8, 'is_blank' => false])]);

    $tiles = array_fill(0, 8, ['letter' => 'A', 'points' => 1]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/games/{$game->ulid}/swap", [
            'tiles' => $tiles,
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['tiles']);
});

it('returns 401 for unauthenticated request', function (): void {
    $game = createGameWithPlayers();

    $response = $this->postJson("/api/games/{$game->ulid}/swap", [
        'tiles' => [['letter' => 'A', 'points' => 1]],
    ]);

    $response->assertStatus(401);
});

it('rejects swap when tiles not in rack', function (): void {
    $user = User::factory()->create();
    $game = createGameWithPlayers(player1: $user);
    $game->update(['tile_bag' => array_fill(0, 10, ['letter' => 'X', 'points' => 8, 'is_blank' => false])]);

    $gamePlayer = $game->gamePlayers->where('user_id', $user->id)->first();
    $gamePlayer->update([
        'rack_tiles' => [
            ['letter' => 'A', 'points' => 1, 'is_blank' => false],
            ['letter' => 'B', 'points' => 3, 'is_blank' => false],
        ],
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/games/{$game->ulid}/swap", [
            'tiles' => [['letter' => 'Z', 'points' => 10]],
        ]);

    $response->assertStatus(422);
});

it('does not advance turn on first swap (free swap)', function (): void {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $game = createGameWithPlayers(player1: $user1, player2: $user2);
    $game->update(['tile_bag' => array_fill(0, 10, ['letter' => 'X', 'points' => 8, 'is_blank' => false])]);

    $gamePlayer = $game->gamePlayers->where('user_id', $user1->id)->first();
    $gamePlayer->update([
        'rack_tiles' => [['letter' => 'A', 'points' => 1, 'is_blank' => false]],
    ]);

    expect($game->current_turn_user_id)->toBe($user1->id);
    expect($gamePlayer->has_free_swap)->toBeTrue();

    $this->actingAs($user1, 'sanctum')
        ->postJson("/api/games/{$game->ulid}/swap", [
            'tiles' => [['letter' => 'A', 'points' => 1]],
        ])
        ->assertOk();

    expect($game->fresh()->current_turn_user_id)->toBe($user1->id);
    expect($gamePlayer->fresh()->has_free_swap)->toBeFalse();
});

it('advances turn to next player after second swap', function (): void {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $game = createGameWithPlayers(player1: $user1, player2: $user2);
    $game->update(['tile_bag' => array_fill(0, 10, ['letter' => 'X', 'points' => 8, 'is_blank' => false])]);

    $gamePlayer = $game->gamePlayers->where('user_id', $user1->id)->first();
    $gamePlayer->update([
        'rack_tiles' => [
            ['letter' => 'A', 'points' => 1, 'is_blank' => false],
            ['letter' => 'B', 'points' => 3, 'is_blank' => false],
        ],
        'has_free_swap' => false,
    ]);

    expect($game->current_turn_user_id)->toBe($user1->id);

    $this->actingAs($user1, 'sanctum')
        ->postJson("/api/games/{$game->ulid}/swap", [
            'tiles' => [['letter' => 'A', 'points' => 1]],
        ])
        ->assertOk();

    expect($game->fresh()->current_turn_user_id)->toBe($user2->id);
});

it('resets consecutive passes to zero after swap', function (): void {
    $user = User::factory()->create();
    $game = createGameWithPlayers(player1: $user);
    $game->update([
        'tile_bag' => array_fill(0, 10, ['letter' => 'X', 'points' => 8, 'is_blank' => false]),
        'consecutive_passes' => 3,
    ]);

    $gamePlayer = $game->gamePlayers->where('user_id', $user->id)->first();
    $gamePlayer->update([
        'rack_tiles' => [['letter' => 'A', 'points' => 1, 'is_blank' => false]],
    ]);

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/games/{$game->ulid}/swap", [
            'tiles' => [['letter' => 'A', 'points' => 1]],
        ])
        ->assertOk();

    expect($game->fresh()->consecutive_passes)->toBe(0);
});

it('draws new tiles from bag and updates rack', function (): void {
    $user = User::factory()->create();
    $game = createGameWithPlayers(player1: $user);
    $game->update([
        'tile_bag' => [
            ['letter' => 'Z', 'points' => 10, 'is_blank' => false],
            ['letter' => 'Q', 'points' => 10, 'is_blank' => false],
            ['letter' => 'X', 'points' => 8, 'is_blank' => false],
            ['letter' => 'X', 'points' => 8, 'is_blank' => false],
            ['letter' => 'X', 'points' => 8, 'is_blank' => false],
            ['letter' => 'X', 'points' => 8, 'is_blank' => false],
            ['letter' => 'X', 'points' => 8, 'is_blank' => false],
        ],
    ]);

    $gamePlayer = $game->gamePlayers->where('user_id', $user->id)->first();
    $gamePlayer->update([
        'rack_tiles' => [
            ['letter' => 'A', 'points' => 1, 'is_blank' => false],
            ['letter' => 'B', 'points' => 3, 'is_blank' => false],
            ['letter' => 'C', 'points' => 3, 'is_blank' => false],
        ],
        'has_received_blank' => true,
    ]);

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/games/{$game->ulid}/swap", [
            'tiles' => [
                ['letter' => 'A', 'points' => 1],
                ['letter' => 'B', 'points' => 3],
            ],
        ])
        ->assertOk();

    $updatedRack = $gamePlayer->fresh()->rack_tiles;
    $rackLetters = collect($updatedRack)->pluck('letter')->all();

    expect($updatedRack)->toHaveCount(3)
        ->and($rackLetters)->toContain('C')
        ->and($rackLetters)->toContain('Z')
        ->and($rackLetters)->toContain('Q');
});

it('returns swapped tiles to bag', function (): void {
    $user = User::factory()->create();
    $game = createGameWithPlayers(player1: $user);
    $game->update([
        'tile_bag' => array_fill(0, 7, ['letter' => 'X', 'points' => 8, 'is_blank' => false]),
    ]);

    $gamePlayer = $game->gamePlayers->where('user_id', $user->id)->first();
    $gamePlayer->update([
        'rack_tiles' => [
            ['letter' => 'A', 'points' => 1, 'is_blank' => false],
            ['letter' => 'B', 'points' => 3, 'is_blank' => false],
        ],
        'has_received_blank' => true,
    ]);

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/games/{$game->ulid}/swap", [
            'tiles' => [
                ['letter' => 'A', 'points' => 1],
                ['letter' => 'B', 'points' => 3],
            ],
        ])
        ->assertOk();

    $updatedBag = $game->fresh()->tile_bag;
    $bagLetters = collect($updatedBag)->pluck('letter');

    expect($updatedBag)->toHaveCount(7)
        ->and($bagLetters)->toContain('A')
        ->and($bagLetters)->toContain('B');
});

it('exposes has_free_swap in player data', function (): void {
    $user = User::factory()->create();
    $game = createGameWithPlayers(player1: $user);
    $game->update(['tile_bag' => array_fill(0, 10, ['letter' => 'X', 'points' => 8, 'is_blank' => false])]);

    $gamePlayer = $game->gamePlayers->where('user_id', $user->id)->first();
    $gamePlayer->update([
        'rack_tiles' => [['letter' => 'A', 'points' => 1, 'is_blank' => false]],
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/games/{$game->ulid}/swap", [
            'tiles' => [['letter' => 'A', 'points' => 1]],
        ]);

    $response->assertOk()
        ->assertJsonPath('data.players.0.has_free_swap', false);
});
