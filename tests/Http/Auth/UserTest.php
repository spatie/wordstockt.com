<?php

use App\Domain\User\Models\User;

it('returns authenticated user data', function (): void {
    $user = User::factory()->create([
        'username' => 'testuser',
        'elo_rating' => 1500,
        'games_played' => 10,
        'games_won' => 6,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/auth/user');

    $response->assertOk()
        ->assertJsonPath('data.ulid', $user->ulid)
        ->assertJsonPath('data.username', 'testuser')
        ->assertJsonPath('data.eloRating', 1500)
        ->assertJsonPath('data.gamesPlayed', 10)
        ->assertJsonPath('data.gamesWon', 6);
});

it('returns 401 for unauthenticated request', function (): void {
    $response = $this->getJson('/api/auth/user');

    $response->assertStatus(401);
});

it('includes email in response', function (): void {
    $user = User::factory()->create([
        'email' => 'test@example.com',
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/auth/user');

    $response->assertOk()
        ->assertJsonPath('data.email', 'test@example.com');
});
