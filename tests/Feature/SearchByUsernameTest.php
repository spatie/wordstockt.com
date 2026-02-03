<?php

use App\Domain\User\Models\User;
use Laravel\Sanctum\Sanctum;

it('can find a user by exact username', function (): void {
    $user = User::factory()->create();
    $targetUser = User::factory()->create(['username' => 'testuser123']);
    Sanctum::actingAs($user);

    $response = $this->getJson('/api/users/search?query=testuser123&exact=true');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.ulid', $targetUser->ulid)
        ->assertJsonPath('data.0.username', 'testuser123')
        ->assertJsonStructure([
            'data' => [['ulid', 'username', 'avatar', 'avatar_color', 'eloRating']],
        ]);
});

it('finds user with case insensitive exact match', function (): void {
    $user = User::factory()->create();
    $targetUser = User::factory()->create(['username' => 'TestUser123']);
    Sanctum::actingAs($user);

    $response = $this->getJson('/api/users/search?query=testuser123&exact=true');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.ulid', $targetUser->ulid);
});

it('returns empty array when exact user not found', function (): void {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->getJson('/api/users/search?query=nonexistent&exact=true');

    $response->assertOk()
        ->assertJsonCount(0, 'data');
});

it('does not return partial matches with exact search', function (): void {
    $user = User::factory()->create();
    User::factory()->create(['username' => 'johnsmith']);
    Sanctum::actingAs($user);

    $response = $this->getJson('/api/users/search?query=john&exact=true');

    $response->assertOk()
        ->assertJsonCount(0, 'data');
});

it('cannot find yourself with exact search', function (): void {
    $user = User::factory()->create(['username' => 'myself']);
    Sanctum::actingAs($user);

    $response = $this->getJson('/api/users/search?query=myself&exact=true');

    $response->assertOk()
        ->assertJsonCount(0, 'data');
});

it('cannot find guest users with exact search', function (): void {
    $user = User::factory()->create();
    User::factory()->create(['username' => 'guestuser', 'is_guest' => true]);
    Sanctum::actingAs($user);

    $response = $this->getJson('/api/users/search?query=guestuser&exact=true');

    $response->assertOk()
        ->assertJsonCount(0, 'data');
});

it('returns multiple partial matches with prefix search', function (): void {
    $user = User::factory()->create();
    User::factory()->create(['username' => 'johnsmith']);
    User::factory()->create(['username' => 'johndoe']);
    Sanctum::actingAs($user);

    $response = $this->getJson('/api/users/search?query=john');

    $response->assertOk()
        ->assertJsonCount(2, 'data');
});

it('excludes guest users from prefix search', function (): void {
    $user = User::factory()->create();
    User::factory()->create(['username' => 'johnsmith']);
    User::factory()->create(['username' => 'johndoe', 'is_guest' => true]);
    Sanctum::actingAs($user);

    $response = $this->getJson('/api/users/search?query=john');

    $response->assertOk()
        ->assertJsonCount(1, 'data');
});

it('requires query parameter for search', function (): void {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->getJson('/api/users/search');

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['query']);
});

it('requires query to be at least 2 characters', function (): void {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->getJson('/api/users/search?query=a');

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['query']);
});

it('requires authentication for search', function (): void {
    $response = $this->getJson('/api/users/search?query=test');

    $response->assertUnauthorized();
});
