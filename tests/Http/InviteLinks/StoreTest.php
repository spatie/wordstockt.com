<?php

use App\Domain\Game\Enums\GameStatus;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\GamePlayer;
use App\Domain\User\Models\GameInviteLink;
use App\Domain\User\Models\User;

it('creates invite link for pending game', function (): void {
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
        ->postJson("/api/games/{$game->ulid}/invite-link");

    $response->assertCreated()
        ->assertJsonStructure(['data' => ['ulid', 'code', 'url', 'game', 'inviter', 'is_used']]);

    expect(GameInviteLink::where('game_id', $game->id)->exists())->toBeTrue();
});

it('generates unique code for each link', function (): void {
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

    $response1 = $this->actingAs($creator, 'sanctum')
        ->postJson("/api/games/{$game->ulid}/invite-link");

    $response2 = $this->actingAs($creator, 'sanctum')
        ->postJson("/api/games/{$game->ulid}/invite-link");

    $code1 = $response1->json('data.code');
    $code2 = $response2->json('data.code');

    expect($code1)->not->toBe($code2);
});

it('fails when non-player tries to create link', function (): void {
    $creator = User::factory()->create();
    $otherUser = User::factory()->create();

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

    $response = $this->actingAs($otherUser, 'sanctum')
        ->postJson("/api/games/{$game->ulid}/invite-link");

    $response->assertForbidden();
});

it('fails when game is not pending', function (): void {
    $game = createGameWithPlayers(status: GameStatus::Active);
    $creator = $game->gamePlayers()->where('turn_order', 1)->first()->user;

    $response = $this->actingAs($creator, 'sanctum')
        ->postJson("/api/games/{$game->ulid}/invite-link");

    $response->assertStatus(422);
});
