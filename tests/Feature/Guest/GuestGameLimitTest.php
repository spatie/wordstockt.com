<?php

use App\Domain\Game\Enums\GameStatus;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\GamePlayer;
use App\Domain\User\Models\User;
use Laravel\Sanctum\Sanctum;

it('allows guest to create up to 3 games', function (): void {
    $guest = User::factory()->guest()->create();
    Sanctum::actingAs($guest);

    for ($i = 0; $i < 3; $i++) {
        $response = $this->postJson('/api/games', ['language' => 'nl']);
        $response->assertCreated();
    }

    expect($guest->games()->count())->toBe(3);
});

it('blocks guest from creating 4th game', function (): void {
    $guest = User::factory()->guest()->create();

    for ($i = 0; $i < 3; $i++) {
        $game = Game::factory()->create(['status' => GameStatus::Active]);
        GamePlayer::factory()->create([
            'game_id' => $game->id,
            'user_id' => $guest->id,
            'turn_order' => 1,
        ]);
    }

    Sanctum::actingAs($guest);

    $response = $this->postJson('/api/games', ['language' => 'nl']);

    $response->assertForbidden()
        ->assertJsonPath('code', 'guest_game_limit');
});

it('allows guest to create game after finishing one', function (): void {
    $guest = User::factory()->guest()->create();

    for ($i = 0; $i < 3; $i++) {
        $game = Game::factory()->create(['status' => GameStatus::Active]);
        GamePlayer::factory()->create([
            'game_id' => $game->id,
            'user_id' => $guest->id,
            'turn_order' => 1,
        ]);
    }

    $guest->games()->first()->update(['status' => GameStatus::Finished]);

    Sanctum::actingAs($guest);

    $response = $this->postJson('/api/games', ['language' => 'nl']);

    $response->assertCreated();
});

it('counts pending games towards limit', function (): void {
    $guest = User::factory()->guest()->create();

    for ($i = 0; $i < 3; $i++) {
        $game = Game::factory()->create(['status' => GameStatus::Pending]);
        GamePlayer::factory()->create([
            'game_id' => $game->id,
            'user_id' => $guest->id,
            'turn_order' => 1,
        ]);
    }

    Sanctum::actingAs($guest);

    $response = $this->postJson('/api/games', ['language' => 'nl']);

    $response->assertForbidden()
        ->assertJsonPath('code', 'guest_game_limit');
});

it('does not limit regular users', function (): void {
    $user = User::factory()->create();

    for ($i = 0; $i < 5; $i++) {
        $game = Game::factory()->create(['status' => GameStatus::Active]);
        GamePlayer::factory()->create([
            'game_id' => $game->id,
            'user_id' => $user->id,
            'turn_order' => 1,
        ]);
    }

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/games', ['language' => 'nl']);

    $response->assertCreated();
});
