<?php

use App\Domain\Game\Enums\GameStatus;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\Route;

it('returns 403 for not a player error', function (): void {
    $user = User::factory()->create();
    $game = createGameWithPlayers();

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/games/{$game->ulid}");

    $response->assertStatus(403);
});

it('returns 403 for game not pending error', function (): void {
    $user = User::factory()->create();
    $game = createGameWithPlayers(status: GameStatus::Active);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/games/{$game->ulid}/join");

    $response->assertStatus(403);
});

it('returns 403 for already joined error', function (): void {
    $user = User::factory()->create();
    $game = createGameWithPlayers(player1: $user, status: GameStatus::Pending);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/games/{$game->ulid}/join");

    $response->assertStatus(403);
});

it('returns 422 with message for not your turn error', function (): void {
    $player1 = User::factory()->create();
    $player2 = User::factory()->create();
    $game = createGameWithPlayers(player1: $player1, player2: $player2);

    $response = $this->actingAs($player2, 'sanctum')
        ->postJson("/api/games/{$game->ulid}/pass");

    $response->assertStatus(422)
        ->assertJson(['message' => 'It is not your turn.']);
});

it('returns 422 with message for game not active error', function (): void {
    $player1 = User::factory()->create();
    $game = createGameWithPlayers(player1: $player1, status: GameStatus::Finished);

    $response = $this->actingAs($player1, 'sanctum')
        ->postJson("/api/games/{$game->ulid}/pass");

    $response->assertStatus(422)
        ->assertJson(['message' => 'Game is not active.']);
});

it('returns JSON for 404 errors when Accept header is application/json', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/nonexistent-route');

    $response->assertStatus(404)
        ->assertHeader('Content-Type', 'application/json')
        ->assertJsonStructure(['message']);
});

it('returns JSON for 500 errors when Accept header is application/json', function (): void {
    // Register a route that throws an exception
    Route::get('/api/test-error', fn () => throw new Exception('Test error'));

    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/test-error');

    $response->assertStatus(500)
        ->assertHeader('Content-Type', 'application/json');
});
