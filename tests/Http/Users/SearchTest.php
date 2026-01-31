<?php

use App\Domain\User\Models\User;

it('finds users by username prefix', function (): void {
    $user = User::factory()->create();
    User::factory()->create(['username' => 'johndoe']);
    User::factory()->create(['username' => 'johnsmith']);
    User::factory()->create(['username' => 'janedoe']);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/users/search?query=john');

    $response->assertOk()
        ->assertJsonCount(2, 'data');
});

it('excludes current user from results', function (): void {
    $user = User::factory()->create(['username' => 'johnme']);
    User::factory()->create(['username' => 'johndoe']);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/users/search?query=john');

    $response->assertOk()
        ->assertJsonCount(1, 'data');

    expect($response->json('data.0.username'))->toBe('johndoe');
});

it('returns user details in results', function (): void {
    $user = User::factory()->create();
    User::factory()->create([
        'username' => 'testuser',
        'elo_rating' => 1500,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/users/search?query=test');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => ['ulid', 'username', 'avatar', 'eloRating'],
            ],
        ]);
});

it('requires minimum 2 character query', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/users/search?query=j');

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['query']);
});

it('requires query parameter', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/users/search');

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['query']);
});

it('limits results to 10 users', function (): void {
    $user = User::factory()->create();

    // Create 15 users with matching prefix
    for ($i = 0; $i < 15; $i++) {
        User::factory()->create(['username' => "testuser{$i}"]);
    }

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/users/search?query=test');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(10);
});

it('returns empty array when no matches', function (): void {
    $user = User::factory()->create();
    User::factory()->create(['username' => 'alice']);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/users/search?query=bob');

    $response->assertOk();
    expect($response->json('data'))->toBeEmpty();
});

it('is case insensitive', function (): void {
    $user = User::factory()->create();
    User::factory()->create(['username' => 'JohnDoe']);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/users/search?query=john');

    $response->assertOk()
        ->assertJsonCount(1, 'data');
});

it('returns 401 for unauthenticated request', function (): void {
    $response = $this->getJson('/api/users/search?query=test');

    $response->assertStatus(401);
});
