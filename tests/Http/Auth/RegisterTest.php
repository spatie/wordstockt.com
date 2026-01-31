<?php

use App\Domain\User\Models\User;

it('registers a new user successfully', function (): void {
    $response = $this->postJson('/api/auth/register', [
        'username' => 'testuser',
        'email' => 'test@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'data' => ['ulid', 'username', 'email', 'eloRating'],
            'token',
        ]);

    expect(User::where('email', 'test@example.com')->exists())->toBeTrue();
});

it('returns user with default elo rating', function (): void {
    $response = $this->postJson('/api/auth/register', [
        'username' => 'newplayer',
        'email' => 'new@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.eloRating', 1200);
});

it('fails with invalid email', function (): void {
    $response = $this->postJson('/api/auth/register', [
        'username' => 'testuser',
        'email' => 'invalid-email',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

it('fails with duplicate email', function (): void {
    User::factory()->create(['email' => 'existing@example.com']);

    $response = $this->postJson('/api/auth/register', [
        'username' => 'testuser',
        'email' => 'existing@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

it('fails with duplicate username', function (): void {
    User::factory()->create(['username' => 'existinguser']);

    $response = $this->postJson('/api/auth/register', [
        'username' => 'existinguser',
        'email' => 'new@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['username']);
});

it('fails with short password', function (): void {
    $response = $this->postJson('/api/auth/register', [
        'username' => 'testuser',
        'email' => 'test@example.com',
        'password' => 'short',
        'password_confirmation' => 'short',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});

it('fails with mismatched password confirmation', function (): void {
    $response = $this->postJson('/api/auth/register', [
        'username' => 'testuser',
        'email' => 'test@example.com',
        'password' => 'password123',
        'password_confirmation' => 'different123',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});

it('fails with invalid username characters', function (): void {
    $response = $this->postJson('/api/auth/register', [
        'username' => 'test user!',
        'email' => 'test@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['username']);
});

it('fails with username too short', function (): void {
    $response = $this->postJson('/api/auth/register', [
        'username' => 'ab',
        'email' => 'test@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['username']);
});
