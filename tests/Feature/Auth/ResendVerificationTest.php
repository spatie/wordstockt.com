<?php

use App\Domain\User\Models\User;
use App\Domain\User\Notifications\VerifyEmailNotification;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;

it('can resend verification email to unverified user', function (): void {
    Notification::fake();

    $user = User::factory()->unverified()->create();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/auth/resend-verification');

    $response->assertOk()
        ->assertJson(['message' => 'Verification email sent.']);

    Notification::assertSentTo($user, VerifyEmailNotification::class);
});

it('does not send verification email to already verified user', function (): void {
    Notification::fake();

    $user = User::factory()->create(['email_verified_at' => now()]);
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/auth/resend-verification');

    $response->assertOk();

    Notification::assertNotSentTo($user, VerifyEmailNotification::class);
});

it('requires authentication to resend verification email', function (): void {
    $response = $this->postJson('/api/auth/resend-verification');

    $response->assertUnauthorized();
});

it('is rate limited', function (): void {
    $user = User::factory()->unverified()->create();
    Sanctum::actingAs($user);

    // Make 6 requests (the limit)
    for ($i = 0; $i < 6; $i++) {
        $this->postJson('/api/auth/resend-verification')->assertOk();
    }

    // 7th request should be rate limited
    $response = $this->postJson('/api/auth/resend-verification');

    $response->assertStatus(429);
});
