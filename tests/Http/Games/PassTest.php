<?php

use App\Domain\Game\Enums\GameStatus;
use App\Domain\User\Models\User;

it('allows current player to pass', function (): void {
    $user = User::factory()->create();
    $game = createGameWithPlayers(player1: $user);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/games/{$game->ulid}/pass");

    $response->assertOk()
        ->assertJsonStructure([
            'move' => ['ulid', 'type'],
            'data',
        ]);
});

it('returns pass move type', function (): void {
    $user = User::factory()->create();
    $game = createGameWithPlayers(player1: $user);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/games/{$game->ulid}/pass");

    $response->assertOk()
        ->assertJsonPath('move.type', 'pass');
});

it('switches turn to other player', function (): void {
    $user = User::factory()->create();
    $opponent = User::factory()->create();
    $game = createGameWithPlayers(player1: $user, player2: $opponent);

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/games/{$game->ulid}/pass");

    $game->refresh();
    expect($game->current_turn_user_id)->toBe($opponent->id);
});

it('rejects pass when not player turn', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $game = createGameWithPlayers(player1: $otherUser, player2: $user);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/games/{$game->ulid}/pass");

    $response->assertStatus(422);
});

it('rejects pass in finished game', function (): void {
    $user = User::factory()->create();
    $game = createGameWithPlayers(player1: $user, status: GameStatus::Finished);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/games/{$game->ulid}/pass");

    $response->assertStatus(422);
});

it('rejects pass for non-player', function (): void {
    $user = User::factory()->create();
    $game = createGameWithPlayers();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/games/{$game->ulid}/pass");

    $response->assertStatus(403);
});

it('returns 401 for unauthenticated request', function (): void {
    $game = createGameWithPlayers();

    $response = $this->postJson("/api/games/{$game->ulid}/pass");

    $response->assertStatus(401);
});
