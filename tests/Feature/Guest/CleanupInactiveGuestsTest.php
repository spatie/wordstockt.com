<?php

use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\GamePlayer;
use App\Domain\User\Models\User;

it('deletes guest users inactive for more than 60 days with no games', function (): void {
    $inactiveGuest = User::factory()->guest()->create([
        'updated_at' => now()->subDays(61),
    ]);

    $this->artisan('users:cleanup-inactive-guests')
        ->expectsOutputToContain('Deleted 1 inactive guest accounts')
        ->assertSuccessful();

    expect(User::find($inactiveGuest->id))->toBeNull();
});

it('does not delete guest users inactive for exactly 60 days', function (): void {
    $guest = User::factory()->guest()->create([
        'updated_at' => now()->subDays(60),
    ]);

    $this->artisan('users:cleanup-inactive-guests')
        ->expectsOutputToContain('Deleted 0 inactive guest accounts')
        ->assertSuccessful();

    expect(User::find($guest->id))->not->toBeNull();
});

it('does not delete guest users inactive for less than 60 days', function (): void {
    $guest = User::factory()->guest()->create([
        'updated_at' => now()->subDays(59),
    ]);

    $this->artisan('users:cleanup-inactive-guests')
        ->expectsOutputToContain('Deleted 0 inactive guest accounts')
        ->assertSuccessful();

    expect(User::find($guest->id))->not->toBeNull();
});

it('does not delete regular users even if inactive', function (): void {
    $regularUser = User::factory()->create([
        'updated_at' => now()->subDays(100),
    ]);

    $this->artisan('users:cleanup-inactive-guests')
        ->expectsOutputToContain('Deleted 0 inactive guest accounts')
        ->assertSuccessful();

    expect(User::find($regularUser->id))->not->toBeNull();
});

it('does not delete inactive guest with an active game', function (): void {
    $guest = User::factory()->guest()->create([
        'updated_at' => now()->subDays(61),
    ]);

    $game = Game::factory()->active()->create();
    GamePlayer::create([
        'game_id' => $game->id,
        'user_id' => $guest->id,
        'rack_tiles' => json_encode([]),
        'score' => 0,
        'turn_order' => 1,
    ]);

    $this->artisan('users:cleanup-inactive-guests')
        ->expectsOutputToContain('Deleted 0 inactive guest accounts')
        ->assertSuccessful();

    expect(User::find($guest->id))->not->toBeNull();
});

it('does not delete inactive guest with a pending game', function (): void {
    $guest = User::factory()->guest()->create([
        'updated_at' => now()->subDays(61),
    ]);

    $game = Game::factory()->pending()->create();
    GamePlayer::create([
        'game_id' => $game->id,
        'user_id' => $guest->id,
        'rack_tiles' => json_encode([]),
        'score' => 0,
        'turn_order' => 1,
    ]);

    $this->artisan('users:cleanup-inactive-guests')
        ->expectsOutputToContain('Deleted 0 inactive guest accounts')
        ->assertSuccessful();

    expect(User::find($guest->id))->not->toBeNull();
});

it('deletes inactive guest who only has finished games', function (): void {
    $guest = User::factory()->guest()->create([
        'updated_at' => now()->subDays(61),
    ]);

    $game = Game::factory()->finished()->create();
    GamePlayer::create([
        'game_id' => $game->id,
        'user_id' => $guest->id,
        'rack_tiles' => json_encode([]),
        'score' => 0,
        'turn_order' => 1,
    ]);

    $this->artisan('users:cleanup-inactive-guests')
        ->expectsOutputToContain('Deleted 1 inactive guest accounts')
        ->assertSuccessful();

    expect(User::find($guest->id))->toBeNull();
});

it('deletes multiple inactive guests at once', function (): void {
    User::factory()->guest()->count(3)->create([
        'updated_at' => now()->subDays(61),
    ]);

    $this->artisan('users:cleanup-inactive-guests')
        ->expectsOutputToContain('Deleted 3 inactive guest accounts')
        ->assertSuccessful();

    expect(User::where('is_guest', true)->count())->toBe(0);
});

it('only deletes guests that meet all criteria', function (): void {
    $shouldDelete = User::factory()->guest()->create([
        'updated_at' => now()->subDays(61),
    ]);

    $recentGuest = User::factory()->guest()->create([
        'updated_at' => now()->subDays(30),
    ]);

    $guestWithActiveGame = User::factory()->guest()->create([
        'updated_at' => now()->subDays(61),
    ]);
    $game = Game::factory()->active()->create();
    GamePlayer::create([
        'game_id' => $game->id,
        'user_id' => $guestWithActiveGame->id,
        'rack_tiles' => json_encode([]),
        'score' => 0,
        'turn_order' => 1,
    ]);

    $regularUser = User::factory()->create([
        'updated_at' => now()->subDays(100),
    ]);

    $this->artisan('users:cleanup-inactive-guests')
        ->expectsOutputToContain('Deleted 1 inactive guest accounts')
        ->assertSuccessful();

    expect(User::find($shouldDelete->id))->toBeNull()
        ->and(User::find($recentGuest->id))->not->toBeNull()
        ->and(User::find($guestWithActiveGame->id))->not->toBeNull()
        ->and(User::find($regularUser->id))->not->toBeNull();
});
