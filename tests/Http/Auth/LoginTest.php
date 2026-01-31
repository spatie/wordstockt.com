<?php

use App\Domain\User\Models\User;

it('logs in with valid credentials', function (): void {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password123'),
    ]);

    $response = $this->postJson('/api/auth/login', [
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $response->assertOk()
        ->assertJsonStructure([
            'data' => ['ulid', 'username', 'email'],
            'token',
        ]);
});

it('returns user data on login', function (): void {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'username' => 'testuser',
        'password' => bcrypt('password123'),
        'elo_rating' => 1500,
    ]);

    $response = $this->postJson('/api/auth/login', [
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.username', 'testuser')
        ->assertJsonPath('data.eloRating', 1500);
});

it('fails with wrong password', function (): void {
    User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password123'),
    ]);

    $response = $this->postJson('/api/auth/login', [
        'email' => 'test@example.com',
        'password' => 'wrongpassword',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

it('fails with non-existent email', function (): void {
    $response = $this->postJson('/api/auth/login', [
        'email' => 'nonexistent@example.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(422);
});

it('fails with missing email', function (): void {
    $response = $this->postJson('/api/auth/login', [
        'password' => 'password123',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

it('fails with missing password', function (): void {
    $response = $this->postJson('/api/auth/login', [
        'email' => 'test@example.com',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});

it('logs in with username instead of email', function (): void {
    $user = User::factory()->create([
        'username' => 'testuser',
        'email' => 'test@example.com',
        'password' => bcrypt('password123'),
    ]);

    $response = $this->postJson('/api/auth/login', [
        'email' => 'testuser',
        'password' => 'password123',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.username', 'testuser')
        ->assertJsonPath('data.email', 'test@example.com');
});

it('fails with non-existent username', function (): void {
    $response = $this->postJson('/api/auth/login', [
        'email' => 'nonexistentuser',
        'password' => 'password123',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

it('fails with wrong password using username', function (): void {
    User::factory()->create([
        'username' => 'testuser',
        'password' => bcrypt('password123'),
    ]);

    $response = $this->postJson('/api/auth/login', [
        'email' => 'testuser',
        'password' => 'wrongpassword',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});
