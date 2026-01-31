<?php

use App\Domain\User\Models\User;

it('can create a guest user and return token', function (): void {
    $response = $this->postJson('/api/auth/guest');

    $response->assertCreated()
        ->assertJsonStructure([
            'data' => [
                'ulid',
                'username',
                'isGuest',
            ],
            'token',
        ])
        ->assertJsonPath('data.isGuest', true);

    $user = User::where('ulid', $response->json('data.ulid'))->first();

    expect($user->is_guest)->toBeTrue()
        ->and($user->email)->toBeNull()
        ->and($user->password)->toBeNull()
        ->and($user->username)->toMatch('/^[A-Z][a-z]+[A-Z][a-z]+\d{3}$/');
});

it('generates unique usernames for guests', function (): void {
    $response1 = $this->postJson('/api/auth/guest');
    $response2 = $this->postJson('/api/auth/guest');

    $username1 = $response1->json('data.username');
    $username2 = $response2->json('data.username');

    expect($username1)->not->toBe($username2);
});

it('allows guest to access authenticated routes', function (): void {
    $response = $this->postJson('/api/auth/guest');
    $token = $response->json('token');

    $authResponse = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/auth/user');

    $authResponse->assertOk()
        ->assertJsonPath('data.isGuest', true);
});
