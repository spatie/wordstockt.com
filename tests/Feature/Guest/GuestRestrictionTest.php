<?php

use App\Domain\User\Models\User;
use Laravel\Sanctum\Sanctum;

it('blocks guest from accessing friends list', function (): void {
    $guest = User::factory()->guest()->create();
    Sanctum::actingAs($guest);

    $response = $this->getJson('/api/friends');

    $response->assertForbidden()
        ->assertJsonPath('code', 'guest_restricted');
});

it('blocks guest from accessing leaderboard', function (): void {
    $guest = User::factory()->guest()->create();
    Sanctum::actingAs($guest);

    $response = $this->getJson('/api/users/leaderboard');

    $response->assertForbidden()
        ->assertJsonPath('code', 'guest_restricted');
});

it('blocks guest from accessing user stats', function (): void {
    $guest = User::factory()->guest()->create();
    $otherUser = User::factory()->create();
    Sanctum::actingAs($guest);

    $response = $this->getJson("/api/users/{$otherUser->ulid}/stats");

    $response->assertForbidden()
        ->assertJsonPath('code', 'guest_restricted');
});

it('blocks guest from sending game invitations', function (): void {
    $guest = User::factory()->guest()->create();
    $game = \App\Domain\Game\Models\Game::factory()->create();
    \App\Domain\Game\Models\GamePlayer::factory()->create([
        'game_id' => $game->id,
        'user_id' => $guest->id,
        'turn_order' => 1,
    ]);
    $otherUser = User::factory()->create();
    Sanctum::actingAs($guest);

    $response = $this->postJson("/api/games/{$game->ulid}/invite", [
        'user_ulid' => $otherUser->ulid,
    ]);

    $response->assertForbidden()
        ->assertJsonPath('code', 'guest_restricted');
});

it('blocks guest from accessing achievements', function (): void {
    $guest = User::factory()->guest()->create();
    Sanctum::actingAs($guest);

    $response = $this->getJson('/api/achievements');

    $response->assertForbidden()
        ->assertJsonPath('code', 'guest_restricted');
});

it('allows regular user to access restricted routes', function (): void {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->getJson('/api/friends');

    $response->assertOk();
});
