<?php

use App\Domain\User\Models\User;

it('rate limits login attempts to 5 per minute', function (): void {
    User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password123'),
    ]);

    for ($i = 0; $i < 5; $i++) {
        $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ])->assertStatus(422);
    }

    $this->postJson('/api/auth/login', [
        'email' => 'test@example.com',
        'password' => 'wrongpassword',
    ])->assertStatus(429);
});

it('rate limits registration attempts to 3 per hour', function (): void {
    for ($i = 0; $i < 3; $i++) {
        $this->postJson('/api/auth/register', [
            'username' => "user{$i}",
            'email' => "user{$i}@example.com",
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertStatus(201);
    }

    $this->postJson('/api/auth/register', [
        'username' => 'user4',
        'email' => 'user4@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertStatus(429);
});

it('rate limits game creation to 10 per minute', function (): void {
    $user = User::factory()->create();

    for ($i = 0; $i < 10; $i++) {
        $this->actingAs($user)
            ->postJson('/api/games', ['language' => 'nl'])
            ->assertStatus(201);
    }

    $this->actingAs($user)
        ->postJson('/api/games', ['language' => 'nl'])
        ->assertStatus(429);
});

it('includes rate limit headers in response', function (): void {
    User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password123'),
    ]);

    $response = $this->postJson('/api/auth/login', [
        'email' => 'test@example.com',
        'password' => 'wrongpassword',
    ]);

    $response->assertHeader('X-RateLimit-Limit', 5);
    $response->assertHeader('X-RateLimit-Remaining', 4);
});

it('includes retry-after header when rate limited', function (): void {
    User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password123'),
    ]);

    for ($i = 0; $i < 5; $i++) {
        $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);
    }

    $response = $this->postJson('/api/auth/login', [
        'email' => 'test@example.com',
        'password' => 'wrongpassword',
    ]);

    $response->assertStatus(429);
    $response->assertHeader('Retry-After');
});
