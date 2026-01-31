<?php

use App\Domain\User\Mail\ResetPasswordMail;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

beforeEach(function (): void {
    Mail::fake();
});

it('sends reset email when user exists with email', function (): void {
    $user = User::factory()->create([
        'email' => 'test@example.com',
    ]);

    $response = $this->postJson('/api/auth/forgot-password', [
        'identifier' => 'test@example.com',
    ]);

    $response->assertOk()
        ->assertJson([
            'message' => "If we have an account with that email or username, we've sent a password reset link.",
        ]);

    Mail::assertQueued(ResetPasswordMail::class, fn ($mail) => $mail->hasTo($user->email));

    $this->assertDatabaseHas('password_reset_tokens', [
        'email' => 'test@example.com',
    ]);
});

it('sends reset email when user exists with username', function (): void {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'username' => 'testuser',
    ]);

    $response = $this->postJson('/api/auth/forgot-password', [
        'identifier' => 'testuser',
    ]);

    $response->assertOk();

    Mail::assertQueued(ResetPasswordMail::class, fn ($mail) => $mail->hasTo($user->email));

    $this->assertDatabaseHas('password_reset_tokens', [
        'email' => 'test@example.com',
    ]);
});

it('returns success even when user does not exist', function (): void {
    $response = $this->postJson('/api/auth/forgot-password', [
        'identifier' => 'nonexistent@example.com',
    ]);

    $response->assertOk()
        ->assertJson([
            'message' => "If we have an account with that email or username, we've sent a password reset link.",
        ]);

    Mail::assertNothingSent();
});

it('returns success even when username does not exist', function (): void {
    $response = $this->postJson('/api/auth/forgot-password', [
        'identifier' => 'nonexistentuser',
    ]);

    $response->assertOk();

    Mail::assertNothingSent();
});

it('fails validation when identifier is missing', function (): void {
    $response = $this->postJson('/api/auth/forgot-password', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['identifier']);
});

it('replaces existing token when requesting again', function (): void {
    $user = User::factory()->create([
        'email' => 'test@example.com',
    ]);

    $this->postJson('/api/auth/forgot-password', [
        'identifier' => 'test@example.com',
    ]);

    $firstToken = DB::table('password_reset_tokens')
        ->where('email', 'test@example.com')
        ->value('token');

    $this->postJson('/api/auth/forgot-password', [
        'identifier' => 'test@example.com',
    ]);

    $secondToken = DB::table('password_reset_tokens')
        ->where('email', 'test@example.com')
        ->value('token');

    expect($firstToken)->not->toBe($secondToken);

    $this->assertDatabaseCount('password_reset_tokens', 1);
});
