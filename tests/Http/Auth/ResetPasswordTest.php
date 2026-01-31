<?php

use App\Domain\User\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

it('shows reset form with valid token', function (): void {
    $user = User::factory()->create([
        'email' => 'test@example.com',
    ]);

    $token = 'valid-token-123';

    DB::table('password_reset_tokens')->insert([
        'email' => 'test@example.com',
        'token' => hash('sha256', $token),
        'created_at' => now(),
    ]);

    $response = $this->get("/reset-password/{$token}?email=test@example.com");

    $response->assertOk()
        ->assertViewIs('auth.reset-password');
});

it('shows invalid page with expired token', function (): void {
    $user = User::factory()->create([
        'email' => 'test@example.com',
    ]);

    $token = 'expired-token-123';

    DB::table('password_reset_tokens')->insert([
        'email' => 'test@example.com',
        'token' => hash('sha256', $token),
        'created_at' => now()->subMinutes(61),
    ]);

    $response = $this->get("/reset-password/{$token}?email=test@example.com");

    $response->assertOk()
        ->assertViewIs('auth.reset-password-invalid');
});

it('shows invalid page with wrong token', function (): void {
    $user = User::factory()->create([
        'email' => 'test@example.com',
    ]);

    DB::table('password_reset_tokens')->insert([
        'email' => 'test@example.com',
        'token' => hash('sha256', 'correct-token'),
        'created_at' => now(),
    ]);

    $response = $this->get('/reset-password/wrong-token?email=test@example.com');

    $response->assertOk()
        ->assertViewIs('auth.reset-password-invalid');
});

it('shows invalid page without email parameter', function (): void {
    $response = $this->get('/reset-password/some-token');

    $response->assertOk()
        ->assertViewIs('auth.reset-password-invalid');
});

it('resets password with valid token', function (): void {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => 'oldpassword',
    ]);

    $token = 'valid-token-123';

    DB::table('password_reset_tokens')->insert([
        'email' => 'test@example.com',
        'token' => hash('sha256', $token),
        'created_at' => now(),
    ]);

    $response = $this->post("/reset-password/{$token}", [
        'email' => 'test@example.com',
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ]);

    $response->assertOk()
        ->assertViewIs('auth.reset-password-success');

    $user->refresh();
    expect(Hash::check('newpassword123', $user->password))->toBeTrue();

    $this->assertDatabaseMissing('password_reset_tokens', [
        'email' => 'test@example.com',
    ]);
});

it('revokes all tokens after password reset', function (): void {
    $user = User::factory()->create([
        'email' => 'test@example.com',
    ]);

    $user->createToken('device-1');
    $user->createToken('device-2');

    expect($user->tokens()->count())->toBe(2);

    $token = 'valid-token-123';

    DB::table('password_reset_tokens')->insert([
        'email' => 'test@example.com',
        'token' => hash('sha256', $token),
        'created_at' => now(),
    ]);

    $this->post("/reset-password/{$token}", [
        'email' => 'test@example.com',
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ]);

    expect($user->tokens()->count())->toBe(0);
});

it('fails with password mismatch', function (): void {
    $user = User::factory()->create([
        'email' => 'test@example.com',
    ]);

    $token = 'valid-token-123';

    DB::table('password_reset_tokens')->insert([
        'email' => 'test@example.com',
        'token' => hash('sha256', $token),
        'created_at' => now(),
    ]);

    $response = $this->post("/reset-password/{$token}", [
        'email' => 'test@example.com',
        'password' => 'newpassword123',
        'password_confirmation' => 'differentpassword',
    ]);

    $response->assertSessionHasErrors(['password']);
});

it('fails with short password', function (): void {
    $user = User::factory()->create([
        'email' => 'test@example.com',
    ]);

    $token = 'valid-token-123';

    DB::table('password_reset_tokens')->insert([
        'email' => 'test@example.com',
        'token' => hash('sha256', $token),
        'created_at' => now(),
    ]);

    $response = $this->post("/reset-password/{$token}", [
        'email' => 'test@example.com',
        'password' => 'short',
        'password_confirmation' => 'short',
    ]);

    $response->assertSessionHasErrors(['password']);
});

it('fails with expired token on submit', function (): void {
    $user = User::factory()->create([
        'email' => 'test@example.com',
    ]);

    $token = 'expired-token-123';

    DB::table('password_reset_tokens')->insert([
        'email' => 'test@example.com',
        'token' => hash('sha256', $token),
        'created_at' => now()->subMinutes(61),
    ]);

    $response = $this->post("/reset-password/{$token}", [
        'email' => 'test@example.com',
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ]);

    $response->assertOk()
        ->assertViewIs('auth.reset-password-invalid');
});
