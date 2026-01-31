<?php

use App\Domain\Game\Enums\GameStatus;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\GamePlayer;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;

it('can convert guest to regular user', function (): void {
    Notification::fake();

    $guest = User::factory()->guest()->create();
    Sanctum::actingAs($guest);

    $response = $this->postJson('/api/auth/convert-guest', [
        'username' => 'newusername',
        'email' => 'test@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.username', 'newusername')
        ->assertJsonPath('data.email', 'test@example.com')
        ->assertJsonPath('data.isGuest', false);

    $guest->refresh();

    expect($guest->is_guest)->toBeFalse()
        ->and($guest->email)->toBe('test@example.com')
        ->and($guest->username)->toBe('newusername')
        ->and($guest->password)->not->toBeNull();
});

it('preserves games when converting guest', function (): void {
    Notification::fake();

    $guest = User::factory()->guest()->create();

    $game = Game::factory()->create(['status' => GameStatus::Active]);
    GamePlayer::factory()->create([
        'game_id' => $game->id,
        'user_id' => $guest->id,
        'turn_order' => 1,
        'score' => 50,
    ]);

    Sanctum::actingAs($guest);

    $response = $this->postJson('/api/auth/convert-guest', [
        'username' => 'newusername',
        'email' => 'test@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertOk();

    $guest->refresh();

    expect($guest->games()->count())->toBe(1)
        ->and($guest->games()->first()->gamePlayers()->where('user_id', $guest->id)->first()->score)->toBe(50);
});

it('allows guest to keep their own username when converting', function (): void {
    Notification::fake();

    $guest = User::factory()->guest()->create(['username' => 'WisePlayer123']);
    Sanctum::actingAs($guest);

    $response = $this->postJson('/api/auth/convert-guest', [
        'username' => 'WisePlayer123',
        'email' => 'test@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.username', 'WisePlayer123');
});

it('requires unique username when converting', function (): void {
    User::factory()->create(['username' => 'existinguser']);

    $guest = User::factory()->guest()->create();
    Sanctum::actingAs($guest);

    $response = $this->postJson('/api/auth/convert-guest', [
        'username' => 'existinguser',
        'email' => 'test@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['username']);
});

it('requires unique email when converting', function (): void {
    User::factory()->create(['email' => 'existing@example.com']);

    $guest = User::factory()->guest()->create();
    Sanctum::actingAs($guest);

    $response = $this->postJson('/api/auth/convert-guest', [
        'username' => 'newusername',
        'email' => 'existing@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

it('prevents non-guest from converting', function (): void {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/auth/convert-guest', [
        'username' => 'newusername',
        'email' => 'test@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertForbidden();
});

it('sends verification email after conversion', function (): void {
    Notification::fake();

    $guest = User::factory()->guest()->create();
    Sanctum::actingAs($guest);

    $this->postJson('/api/auth/convert-guest', [
        'username' => 'newusername',
        'email' => 'test@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    Notification::assertSentTo($guest->refresh(), \App\Domain\User\Notifications\VerifyEmailNotification::class);
});
