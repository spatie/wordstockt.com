<?php

use App\Domain\User\Models\User;

it('returns user profile', function (): void {
    $user = User::factory()->create();
    $profile = User::factory()->create([
        'username' => 'profileuser',
        'elo_rating' => 1600,
        'games_played' => 10,
        'games_won' => 6,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/users/{$profile->ulid}");

    $response->assertOk()
        ->assertJsonPath('data.username', 'profileuser')
        ->assertJsonPath('data.eloRating', 1600)
        ->assertJsonPath('data.gamesPlayed', 10)
        ->assertJsonPath('data.gamesWon', 6);
});

it('calculates win rate correctly', function (): void {
    $user = User::factory()->create();
    $profile = User::factory()->create([
        'games_played' => 10,
        'games_won' => 6,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/users/{$profile->ulid}");

    $response->assertOk()
        ->assertJsonPath('data.winRate', 60);
});

it('returns 0 win rate for no games played', function (): void {
    $user = User::factory()->create();
    $profile = User::factory()->create([
        'games_played' => 0,
        'games_won' => 0,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/users/{$profile->ulid}");

    $response->assertOk()
        ->assertJsonPath('data.winRate', 0);
});

it('includes avatar in response', function (): void {
    $user = User::factory()->create();
    $profile = User::factory()->create(['avatar' => 'https://example.com/avatar.png']);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/users/{$profile->ulid}");

    $response->assertOk()
        ->assertJsonPath('data.avatar', 'https://example.com/avatar.png');
});

it('can view own profile', function (): void {
    $user = User::factory()->create(['username' => 'myname']);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/users/{$user->ulid}");

    $response->assertOk()
        ->assertJsonPath('data.username', 'myname');
});

it('returns 404 for non-existent user', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/users/01h1234567890abcdefghjkmnp');

    $response->assertStatus(404);
});

it('returns 401 for unauthenticated request', function (): void {
    $profile = User::factory()->create();

    $response = $this->getJson("/api/users/{$profile->ulid}");

    $response->assertStatus(401);
});
