<?php

use App\Domain\Game\Enums\GameStatus;
use App\Domain\User\Models\User;

it('creates a new game', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/games', [
            'language' => 'en',
        ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'data' => ['ulid', 'status', 'language', 'board'],
        ]);

    expect($response->json('data.status'))->toBe(GameStatus::Pending->value);
});

it('creates a game with Dutch language', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/games', [
            'language' => 'nl',
        ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.language', 'nl');
});

it('defaults to Dutch language', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/games', []);

    $response->assertStatus(201)
        ->assertJsonPath('data.language', 'nl');
});

it('creates a game with specific opponent', function (): void {
    $user = User::factory()->create();
    $opponent = User::factory()->create(['username' => 'opponent']);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/games', [
            'language' => 'en',
            'opponent_username' => 'opponent',
        ]);

    $response->assertStatus(201);
});

it('fails with invalid language', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/games', [
            'language' => 'invalid',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['language']);
});

it('fails with non-existent opponent username', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/games', [
            'opponent_username' => 'nonexistent',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['opponent_username']);
});

it('returns 401 for unauthenticated request', function (): void {
    $response = $this->postJson('/api/games', [
        'language' => 'en',
    ]);

    $response->assertStatus(401);
});
