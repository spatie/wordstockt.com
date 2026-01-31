<?php

use App\Domain\Game\Enums\GameStatus;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\GamePlayer;
use App\Domain\User\Models\GameInviteLink;
use App\Domain\User\Models\User;

it('shows invite link details', function (): void {
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

    $link = GameInviteLink::create([
        'game_id' => $game->id,
        'inviter_id' => $creator->id,
    ]);

    $response = $this->actingAs($creator, 'sanctum')
        ->getJson("/api/invite-links/{$link->code}");

    $response->assertOk()
        ->assertJsonPath('data.code', $link->code)
        ->assertJsonPath('data.game.ulid', $game->ulid)
        ->assertJsonPath('data.inviter.ulid', $creator->ulid)
        ->assertJsonPath('data.is_used', false);
});

it('returns 404 for non-existent code', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/invite-links/NONEXIST');

    $response->assertStatus(404);
});
