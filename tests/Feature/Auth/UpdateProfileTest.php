<?php

use App\Domain\User\Models\User;
use App\Domain\User\Notifications\VerifyEmailNotification;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;

it('can update username', function (): void {
    $user = User::factory()->create(['username' => 'oldusername']);
    Sanctum::actingAs($user);

    $response = $this->putJson('/api/auth/user', [
        'username' => 'newusername',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.username', 'newusername');

    expect($user->fresh()->username)->toBe('newusername');
});

it('can update email and resets verification', function (): void {
    Notification::fake();

    $user = User::factory()->create([
        'email' => 'old@example.com',
        'email_verified_at' => now(),
    ]);
    Sanctum::actingAs($user);

    $response = $this->putJson('/api/auth/user', [
        'email' => 'new@example.com',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.email', 'new@example.com');

    $user->refresh();

    expect($user->email)->toBe('new@example.com')
        ->and($user->email_verified_at)->toBeNull();

    Notification::assertSentTo($user, VerifyEmailNotification::class);
});

it('does not reset verification when email unchanged', function (): void {
    Notification::fake();

    $verifiedAt = now();
    $user = User::factory()->create([
        'email' => 'same@example.com',
        'email_verified_at' => $verifiedAt,
    ]);
    Sanctum::actingAs($user);

    $response = $this->putJson('/api/auth/user', [
        'username' => 'newusername',
    ]);

    $response->assertOk();

    expect($user->fresh()->email_verified_at)->not->toBeNull();

    Notification::assertNotSentTo($user, VerifyEmailNotification::class);
});

it('validates unique email', function (): void {
    User::factory()->create(['email' => 'taken@example.com']);

    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->putJson('/api/auth/user', [
        'email' => 'taken@example.com',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

it('can update avatar color', function (): void {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->putJson('/api/auth/user', [
        'avatar_color' => '#FF5733',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.avatarColor', '#FF5733');
});
