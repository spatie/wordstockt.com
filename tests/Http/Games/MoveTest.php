<?php

use App\Domain\Game\Enums\GameStatus;
use App\Domain\User\Models\User;

it('validates tile coordinates', function (): void {
    $user = User::factory()->create();
    $game = createGameWithPlayers(player1: $user);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/games/{$game->ulid}/moves", [
            'tiles' => [
                ['letter' => 'A', 'x' => 20, 'y' => 7, 'points' => 1],
            ],
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['tiles.0.x']);
});

it('validates y coordinate max value', function (): void {
    $user = User::factory()->create();
    $game = createGameWithPlayers(player1: $user);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/games/{$game->ulid}/moves", [
            'tiles' => [
                ['letter' => 'A', 'x' => 7, 'y' => 20, 'points' => 1],
            ],
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['tiles.0.y']);
});

it('validates tiles array is required', function (): void {
    $user = User::factory()->create();
    $game = createGameWithPlayers(player1: $user);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/games/{$game->ulid}/moves", []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['tiles']);
});

it('validates tiles array is not empty', function (): void {
    $user = User::factory()->create();
    $game = createGameWithPlayers(player1: $user);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/games/{$game->ulid}/moves", [
            'tiles' => [],
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['tiles']);
});

it('validates letter is required', function (): void {
    $user = User::factory()->create();
    $game = createGameWithPlayers(player1: $user);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/games/{$game->ulid}/moves", [
            'tiles' => [
                ['x' => 7, 'y' => 7, 'points' => 1],
            ],
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['tiles.0.letter']);
});

it('validates points is required', function (): void {
    $user = User::factory()->create();
    $game = createGameWithPlayers(player1: $user);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/games/{$game->ulid}/moves", [
            'tiles' => [
                ['letter' => 'A', 'x' => 7, 'y' => 7],
            ],
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['tiles.0.points']);
});

it('rejects move when not player turn', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $game = createGameWithPlayers(player1: $otherUser, player2: $user);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/games/{$game->ulid}/moves", [
            'tiles' => [
                ['letter' => 'A', 'x' => 7, 'y' => 7, 'points' => 1, 'is_blank' => false],
            ],
        ]);

    $response->assertStatus(422);
});

it('rejects move in finished game', function (): void {
    $user = User::factory()->create();
    $game = createGameWithPlayers(player1: $user, status: GameStatus::Finished);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/games/{$game->ulid}/moves", [
            'tiles' => [
                ['letter' => 'A', 'x' => 7, 'y' => 7, 'points' => 1, 'is_blank' => false],
            ],
        ]);

    $response->assertStatus(422);
});

it('rejects move for non-player', function (): void {
    $user = User::factory()->create();
    $game = createGameWithPlayers();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/games/{$game->ulid}/moves", [
            'tiles' => [
                ['letter' => 'A', 'x' => 7, 'y' => 7, 'points' => 1, 'is_blank' => false],
            ],
        ]);

    $response->assertStatus(403);
});

it('returns 401 for unauthenticated request', function (): void {
    $game = createGameWithPlayers();

    $response = $this->postJson("/api/games/{$game->ulid}/moves", [
        'tiles' => [
            ['letter' => 'A', 'x' => 7, 'y' => 7, 'points' => 1],
        ],
    ]);

    $response->assertStatus(401);
});

it('rejects move with tile not in player rack', function (): void {
    $user = User::factory()->create();
    $game = createGameWithPlayers(player1: $user);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/games/{$game->ulid}/moves", [
            'tiles' => [
                ['letter' => 'Z', 'x' => 7, 'y' => 7, 'points' => 10, 'is_blank' => false],
            ],
        ]);

    $response->assertStatus(422)
        ->assertJsonPath('message', "You don't have the tile 'Z' in your rack.");
});
