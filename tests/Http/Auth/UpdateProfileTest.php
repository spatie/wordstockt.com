<?php

use App\Domain\User\Models\User;

it('updates username', function (): void {
    $user = User::factory()->create(['username' => 'oldname']);

    $response = $this->actingAs($user, 'sanctum')
        ->putJson('/api/auth/user', [
            'username' => 'newname',
        ]);

    $response->assertOk()
        ->assertJsonPath('data.username', 'newname');

    expect($user->fresh()->username)->toBe('newname');
});

it('updates email', function (): void {
    $user = User::factory()->create(['email' => 'old@example.com']);

    $response = $this->actingAs($user, 'sanctum')
        ->putJson('/api/auth/user', [
            'email' => 'new@example.com',
        ]);

    $response->assertOk()
        ->assertJsonPath('data.email', 'new@example.com');
});

it('updates avatar', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->putJson('/api/auth/user', [
            'avatar' => 'https://example.com/avatar.png',
        ]);

    $response->assertOk()
        ->assertJsonPath('data.avatar', 'https://example.com/avatar.png');
});

it('fails when username is taken by another user', function (): void {
    User::factory()->create(['username' => 'takenname']);
    $user = User::factory()->create(['username' => 'myname']);

    $response = $this->actingAs($user, 'sanctum')
        ->putJson('/api/auth/user', [
            'username' => 'takenname',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['username']);
});

it('fails when email is taken by another user', function (): void {
    User::factory()->create(['email' => 'taken@example.com']);
    $user = User::factory()->create(['email' => 'my@example.com']);

    $response = $this->actingAs($user, 'sanctum')
        ->putJson('/api/auth/user', [
            'email' => 'taken@example.com',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

it('allows keeping same username', function (): void {
    $user = User::factory()->create(['username' => 'myname']);

    $response = $this->actingAs($user, 'sanctum')
        ->putJson('/api/auth/user', [
            'username' => 'myname',
        ]);

    $response->assertOk();
});

it('allows keeping same email', function (): void {
    $user = User::factory()->create(['email' => 'my@example.com']);

    $response = $this->actingAs($user, 'sanctum')
        ->putJson('/api/auth/user', [
            'email' => 'my@example.com',
        ]);

    $response->assertOk();
});

it('returns 401 for unauthenticated request', function (): void {
    $response = $this->putJson('/api/auth/user', [
        'username' => 'newname',
    ]);

    $response->assertStatus(401);
});
