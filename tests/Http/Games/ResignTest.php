<?php

use App\Domain\Game\Enums\GameStatus;
use App\Domain\User\Models\User;

it('allows player to resign', function (): void {
    $user = User::factory()->create();
    $game = createGameWithPlayers(player1: $user);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/games/{$game->ulid}/resign");

    $response->assertOk()
        ->assertJsonPath('data.status', GameStatus::Finished->value);
});

it('returns resign move type', function (): void {
    $user = User::factory()->create();
    $game = createGameWithPlayers(player1: $user);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/games/{$game->ulid}/resign");

    $response->assertOk()
        ->assertJsonPath('move.type', 'resign');
});

it('allows resign even when not player turn', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $game = createGameWithPlayers(player1: $otherUser, player2: $user);
    // otherUser has turn, but user should still be able to resign

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/games/{$game->ulid}/resign");

    $response->assertOk();
});

it('sets opponent as winner', function (): void {
    $user = User::factory()->create();
    $opponent = User::factory()->create();
    $game = createGameWithPlayers(player1: $user, player2: $opponent);

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/games/{$game->ulid}/resign");

    $game->refresh();
    expect($game->winner_id)->toBe($opponent->id);
});

it('rejects resign in finished game', function (): void {
    $user = User::factory()->create();
    $game = createGameWithPlayers(player1: $user, status: GameStatus::Finished);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/games/{$game->ulid}/resign");

    $response->assertStatus(422);
});

it('rejects resign for non-player', function (): void {
    $user = User::factory()->create();
    $game = createGameWithPlayers();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/games/{$game->ulid}/resign");

    $response->assertStatus(403);
});

it('returns 401 for unauthenticated request', function (): void {
    $game = createGameWithPlayers();

    $response = $this->postJson("/api/games/{$game->ulid}/resign");

    $response->assertStatus(401);
});

it('increments games_played for both players', function (): void {
    $user = User::factory()->create(['games_played' => 5]);
    $opponent = User::factory()->create(['games_played' => 3]);
    $game = createGameWithPlayers(player1: $user, player2: $opponent);

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/games/{$game->ulid}/resign");

    expect($user->fresh()->games_played)->toBe(6)
        ->and($opponent->fresh()->games_played)->toBe(4);
});

it('increments games_won for winner only', function (): void {
    $user = User::factory()->create(['games_won' => 2]);
    $opponent = User::factory()->create(['games_won' => 1]);
    $game = createGameWithPlayers(player1: $user, player2: $opponent);

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/games/{$game->ulid}/resign");

    expect($user->fresh()->games_won)->toBe(2) // Resigner doesn't win
        ->and($opponent->fresh()->games_won)->toBe(2); // Opponent wins
});

it('returns winner ulid in response', function (): void {
    $user = User::factory()->create();
    $opponent = User::factory()->create();
    $game = createGameWithPlayers(player1: $user, player2: $opponent);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/games/{$game->ulid}/resign");

    $response->assertOk()
        ->assertJsonPath('data.winner_ulid', $opponent->ulid);
});

it('rejects resign in pending game', function (): void {
    $user = User::factory()->create();
    $game = createGameWithPlayers(player1: $user, status: GameStatus::Pending);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/games/{$game->ulid}/resign");

    $response->assertStatus(422);
});

it('creates move record with resign type', function (): void {
    $user = User::factory()->create();
    $game = createGameWithPlayers(player1: $user);

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/games/{$game->ulid}/resign");

    expect($game->moves()->count())->toBe(1)
        ->and($game->moves()->first()->type->value)->toBe('resign')
        ->and($game->moves()->first()->user_id)->toBe($user->id);
});
