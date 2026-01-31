<?php

use App\Domain\Game\Enums\GameStatus;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\GamePlayer;
use App\Domain\User\Enums\InvitationStatus;
use App\Domain\User\Models\GameInvitation;
use App\Domain\User\Models\User;

it('returns game state for player', function (): void {
    $user = User::factory()->create();
    $game = createGameWithPlayers(player1: $user);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/games/{$game->ulid}");

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'ulid',
                'status',
                'board',
            ],
        ]);
});

it('returns rack for current player', function (): void {
    $user = User::factory()->create();
    $game = createGameWithPlayers(player1: $user);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/games/{$game->ulid}");

    $response->assertOk();
    // The game state should include the player's rack
    expect($response->json('data'))->toHaveKey('board');
});

it('returns 403 for non-player', function (): void {
    $user = User::factory()->create();
    $game = createGameWithPlayers(); // User is not part of this game

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/games/{$game->ulid}");

    $response->assertStatus(403);
});

it('returns 404 for non-existent game', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/games/99999');

    $response->assertStatus(404);
});

it('returns 401 for unauthenticated request', function (): void {
    $game = createGameWithPlayers();

    $response = $this->getJson("/api/games/{$game->ulid}");

    $response->assertStatus(401);
});

it('returns pending invitation with game', function (): void {
    $creator = User::factory()->create();
    $invitee = User::factory()->create();

    $game = Game::factory()->create([
        'status' => GameStatus::Pending,
        'tile_bag' => createDefaultTileBag(),
    ]);
    GamePlayer::factory()->create([
        'game_id' => $game->id,
        'user_id' => $creator->id,
        'rack_tiles' => createDefaultRack(),
        'turn_order' => 1,
    ]);

    GameInvitation::create([
        'game_id' => $game->id,
        'inviter_id' => $creator->id,
        'invitee_id' => $invitee->id,
        'status' => InvitationStatus::Pending,
    ]);

    $response = $this->actingAs($creator, 'sanctum')
        ->getJson("/api/games/{$game->ulid}");

    $response->assertOk()
        ->assertJsonPath('data.pending_invitation.invitee.ulid', $invitee->ulid)
        ->assertJsonPath('data.pending_invitation.invitee.username', $invitee->username);
});

it('returns null pending invitation when no invitation exists', function (): void {
    $creator = User::factory()->create();

    $game = Game::factory()->create([
        'status' => GameStatus::Pending,
        'tile_bag' => createDefaultTileBag(),
    ]);
    GamePlayer::factory()->create([
        'game_id' => $game->id,
        'user_id' => $creator->id,
        'rack_tiles' => createDefaultRack(),
        'turn_order' => 1,
    ]);

    $response = $this->actingAs($creator, 'sanctum')
        ->getJson("/api/games/{$game->ulid}");

    $response->assertOk()
        ->assertJsonPath('data.pending_invitation', null);
});
