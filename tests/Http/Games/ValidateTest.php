<?php

use App\Domain\Support\Models\Dictionary;
use App\Domain\User\Models\User;

it('returns 401 for unauthenticated request', function (): void {
    $game = createGameWithPlayers();

    $response = $this->postJson("/api/games/{$game->ulid}/validate", [
        'tiles' => [
            ['letter' => 'A', 'x' => 7, 'y' => 7, 'points' => 1],
        ],
    ]);

    $response->assertStatus(401);
});

it('returns 403 for non-player', function (): void {
    $user = User::factory()->create();
    $game = createGameWithPlayers();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/games/{$game->ulid}/validate", [
            'tiles' => [
                ['letter' => 'A', 'x' => 7, 'y' => 7, 'points' => 1],
            ],
        ]);

    $response->assertStatus(403);
});

it('validates placement errors for gaps', function (): void {
    $user = User::factory()->create();
    $game = createGameWithPlayers(player1: $user);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/games/{$game->ulid}/validate", [
            'tiles' => [
                ['letter' => 'A', 'x' => 7, 'y' => 7, 'points' => 1],
                ['letter' => 'B', 'x' => 9, 'y' => 7, 'points' => 3], // Gap at 8,7
            ],
        ]);

    $response->assertOk()
        ->assertJson([
            'placement_valid' => false,
        ])
        ->assertJsonPath('tile_status.0.valid', false)
        ->assertJsonPath('tile_status.1.valid', false);
});

it('validates first move must cover center', function (): void {
    $user = User::factory()->create();
    $game = createGameWithPlayers(player1: $user);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/games/{$game->ulid}/validate", [
            'tiles' => [
                ['letter' => 'A', 'x' => 0, 'y' => 0, 'points' => 1],
                ['letter' => 'B', 'x' => 1, 'y' => 0, 'points' => 3],
            ],
        ]);

    $response->assertOk()
        ->assertJson([
            'placement_valid' => false,
        ]);
});

it('validates tiles must be in line', function (): void {
    $user = User::factory()->create();
    $game = createGameWithPlayers(player1: $user);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/games/{$game->ulid}/validate", [
            'tiles' => [
                ['letter' => 'A', 'x' => 7, 'y' => 7, 'points' => 1],
                ['letter' => 'B', 'x' => 8, 'y' => 8, 'points' => 3], // Diagonal
            ],
        ]);

    $response->assertOk()
        ->assertJson([
            'placement_valid' => false,
        ]);
});

it('returns valid placement with valid word', function (): void {
    $user = User::factory()->create();
    $game = createGameWithPlayers(player1: $user);

    // Add valid words to dictionary
    Dictionary::create(['word' => 'CAT', 'language' => 'en']);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/games/{$game->ulid}/validate", [
            'tiles' => [
                ['letter' => 'C', 'x' => 7, 'y' => 7, 'points' => 3],
                ['letter' => 'A', 'x' => 8, 'y' => 7, 'points' => 1],
                ['letter' => 'T', 'x' => 9, 'y' => 7, 'points' => 1],
            ],
        ]);

    $response->assertOk()
        ->assertJson([
            'placement_valid' => true,
            'placement_errors' => [],
        ])
        ->assertJsonPath('words.0.word', 'CAT')
        ->assertJsonPath('words.0.valid', true)
        ->assertJsonPath('tile_status.0.valid', true)
        ->assertJsonPath('tile_status.1.valid', true)
        ->assertJsonPath('tile_status.2.valid', true);
});

it('returns invalid word status', function (): void {
    $user = User::factory()->create();
    $game = createGameWithPlayers(player1: $user);

    // XYZ is not in dictionary

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/games/{$game->ulid}/validate", [
            'tiles' => [
                ['letter' => 'X', 'x' => 7, 'y' => 7, 'points' => 8],
                ['letter' => 'Y', 'x' => 8, 'y' => 7, 'points' => 4],
                ['letter' => 'Z', 'x' => 9, 'y' => 7, 'points' => 10],
            ],
        ]);

    $response->assertOk()
        ->assertJson([
            'placement_valid' => true,
        ])
        ->assertJsonPath('words.0.valid', false)
        ->assertJsonPath('tile_status.0.valid', false)
        ->assertJsonPath('tile_status.1.valid', false)
        ->assertJsonPath('tile_status.2.valid', false);
});

it('includes existing board tiles in word tiles', function (): void {
    $user = User::factory()->create();
    $game = createGameWithPlayers(player1: $user);

    // Set up board with existing tile
    $board = createBoardWithTiles([
        ['letter' => 'A', 'x' => 7, 'y' => 7, 'points' => 1],
        ['letter' => 'T', 'x' => 8, 'y' => 7, 'points' => 1],
    ]);
    $game->update(['board_state' => $board]);

    // Add valid words
    Dictionary::create(['word' => 'AT', 'language' => 'en']);
    Dictionary::create(['word' => 'CAT', 'language' => 'en']);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/games/{$game->ulid}/validate", [
            'tiles' => [
                ['letter' => 'C', 'x' => 6, 'y' => 7, 'points' => 3],
            ],
        ]);

    $response->assertOk()
        ->assertJson([
            'placement_valid' => true,
        ])
        ->assertJsonPath('words.0.word', 'CAT')
        ->assertJsonPath('words.0.valid', true);

    // Verify word tiles include all 3 positions (pending C and existing A, T)
    $wordTiles = $response->json('words.0.tiles');
    expect($wordTiles)->toHaveCount(3);
});

it('handles mixed validity when tile in multiple words', function (): void {
    $user = User::factory()->create();
    $game = createGameWithPlayers(player1: $user);

    // Set up board with existing tiles
    // A at (7,7), B at (8,7) - forming "AB" horizontally
    $board = createBoardWithTiles([
        ['letter' => 'A', 'x' => 7, 'y' => 7, 'points' => 1],
        ['letter' => 'B', 'x' => 8, 'y' => 7, 'points' => 3],
    ]);
    $game->update(['board_state' => $board]);

    // Add valid words
    Dictionary::create(['word' => 'TT', 'language' => 'en']); // Main word (horizontal)
    Dictionary::create(['word' => 'AT', 'language' => 'en']); // Perpendicular at first T
    // BT is not valid (perpendicular at second T)

    // Place TT horizontally below AB
    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/games/{$game->ulid}/validate", [
            'tiles' => [
                ['letter' => 'T', 'x' => 7, 'y' => 8, 'points' => 1], // Forms TT horizontally, AT vertically (valid)
                ['letter' => 'T', 'x' => 8, 'y' => 8, 'points' => 1], // Forms TT horizontally, BT vertically (invalid)
            ],
        ]);

    $response->assertOk()
        ->assertJson([
            'placement_valid' => true,
        ]);

    // First T should be valid (TT and AT are both valid)
    // Second T should be invalid (BT is invalid)
    $tileStatus = $response->json('tile_status');
    $firstT = collect($tileStatus)->first(fn ($t): bool => $t['x'] === 7 && $t['y'] === 8);
    $secondT = collect($tileStatus)->first(fn ($t): bool => $t['x'] === 8 && $t['y'] === 8);

    expect($firstT['valid'])->toBeTrue();
    expect($secondT['valid'])->toBeFalse();
});

it('validates tile coordinates', function (): void {
    $user = User::factory()->create();
    $game = createGameWithPlayers(player1: $user);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/games/{$game->ulid}/validate", [
            'tiles' => [
                ['letter' => 'A', 'x' => 20, 'y' => 7, 'points' => 1],
            ],
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['tiles.0.x']);
});

it('validates tiles array is required', function (): void {
    $user = User::factory()->create();
    $game = createGameWithPlayers(player1: $user);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/games/{$game->ulid}/validate", []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['tiles']);
});

it('validates tiles array is not empty', function (): void {
    $user = User::factory()->create();
    $game = createGameWithPlayers(player1: $user);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/games/{$game->ulid}/validate", [
            'tiles' => [],
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['tiles']);
});

it('returns placement error for occupied cell', function (): void {
    $user = User::factory()->create();
    $game = createGameWithPlayers(player1: $user);

    // Set up board with existing tile
    $board = createBoardWithTiles([
        ['letter' => 'A', 'x' => 7, 'y' => 7, 'points' => 1],
    ]);
    $game->update(['board_state' => $board]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/games/{$game->ulid}/validate", [
            'tiles' => [
                ['letter' => 'B', 'x' => 7, 'y' => 7, 'points' => 3], // Same position
            ],
        ]);

    $response->assertOk()
        ->assertJson([
            'placement_valid' => false,
        ]);
});

it('validates second move must connect to existing tiles', function (): void {
    $user = User::factory()->create();
    $game = createGameWithPlayers(player1: $user);

    // Set up board with existing tile
    $board = createBoardWithTiles([
        ['letter' => 'A', 'x' => 7, 'y' => 7, 'points' => 1],
    ]);
    $game->update(['board_state' => $board]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/games/{$game->ulid}/validate", [
            'tiles' => [
                ['letter' => 'B', 'x' => 0, 'y' => 0, 'points' => 3], // Not connected
            ],
        ]);

    $response->assertOk()
        ->assertJson([
            'placement_valid' => false,
        ]);
});

it('marks single letter tiles as invalid when they do not form a word', function (): void {
    $user = User::factory()->create();
    $game = createGameWithPlayers(player1: $user);

    // Set up board with a horizontal word "CAT"
    $board = createBoardWithTiles([
        ['letter' => 'C', 'x' => 7, 'y' => 7, 'points' => 3],
        ['letter' => 'A', 'x' => 8, 'y' => 7, 'points' => 1],
        ['letter' => 'T', 'x' => 9, 'y' => 7, 'points' => 1],
    ]);
    $game->update(['board_state' => $board]);

    // Place a single G tile diagonally adjacent (touching corner only)
    // This tile is connected but doesn't form any 2+ letter word
    // Note: we need to place it adjacent (not diagonal) but in a way that
    // it doesn't form a valid word
    // Place G above C - it only touches C vertically, forming "GC" which is not a word
    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/games/{$game->ulid}/validate", [
            'tiles' => [
                ['letter' => 'G', 'x' => 7, 'y' => 6, 'points' => 2],
            ],
        ]);

    $response->assertOk()
        ->assertJson([
            'placement_valid' => true, // Placement rules pass (connected to existing)
        ]);

    // The G tile should be invalid because "GC" is not a valid word
    $tileStatus = $response->json('tile_status');
    expect($tileStatus)->toHaveCount(1);
    expect($tileStatus[0]['valid'])->toBeFalse();

    // Verify the word "GC" is detected and marked invalid
    $words = $response->json('words');
    expect($words)->toHaveCount(1);
    expect($words[0]['word'])->toBe('GC');
    expect($words[0]['valid'])->toBeFalse();
});

it('marks single tile as invalid when it does not participate in any word', function (): void {
    $user = User::factory()->create();
    $game = createGameWithPlayers(player1: $user);

    // First move: single tile on center
    // This doesn't form any 2+ letter word, so it should be marked invalid
    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/games/{$game->ulid}/validate", [
            'tiles' => [
                ['letter' => 'A', 'x' => 7, 'y' => 7, 'points' => 1],
            ],
        ]);

    $response->assertOk()
        ->assertJson([
            'placement_valid' => true, // Placement rules pass (covers center)
        ]);

    // The tile should be invalid because it doesn't form any word
    $tileStatus = $response->json('tile_status');
    expect($tileStatus)->toHaveCount(1);
    expect($tileStatus[0]['valid'])->toBeFalse();

    // No words should be detected
    $words = $response->json('words');
    expect($words)->toHaveCount(0);
});
