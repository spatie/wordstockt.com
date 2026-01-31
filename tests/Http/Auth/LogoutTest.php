<?php

use App\Domain\User\Models\User;

it('logs out authenticated user', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/auth/logout');

    $response->assertOk()
        ->assertJson(['message' => 'Logged out successfully']);
});

it('returns 401 for unauthenticated request', function (): void {
    $response = $this->postJson('/api/auth/logout');

    $response->assertStatus(401);
});
