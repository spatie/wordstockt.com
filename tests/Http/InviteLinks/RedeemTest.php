<?php

use App\Domain\Game\Enums\GameStatus;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\GamePlayer;
use App\Domain\Game\Notifications\GameInviteAcceptedNotification;
use App\Domain\User\Models\GameInviteLink;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\Notification;

it('redeems invite link and joins the game', function (): void {
    $creator = User::factory()->create();
    $joiner = User::factory()->create();

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

    $response = $this->actingAs($joiner, 'sanctum')
        ->postJson("/api/invite-links/{$link->code}/redeem");

    $response->assertOk()
        ->assertJsonPath('data.status', GameStatus::Active->value);

    expect($link->fresh()->isUsed())->toBeTrue();
    expect($game->fresh()->hasPlayer($joiner))->toBeTrue();
    expect($game->fresh()->status)->toBe(GameStatus::Active);
});

it('notifies inviter when link is redeemed', function (): void {
    Notification::fake();

    $creator = User::factory()->create();
    $joiner = User::factory()->create();

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

    $this->actingAs($joiner, 'sanctum')
        ->postJson("/api/invite-links/{$link->code}/redeem");

    Notification::assertSentTo($creator, GameInviteAcceptedNotification::class);
});

it('fails when link is already used', function (): void {
    $creator = User::factory()->create();
    $firstJoiner = User::factory()->create();
    $secondJoiner = User::factory()->create();

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

    $this->actingAs($firstJoiner, 'sanctum')
        ->postJson("/api/invite-links/{$link->code}/redeem");

    $response = $this->actingAs($secondJoiner, 'sanctum')
        ->postJson("/api/invite-links/{$link->code}/redeem");

    $response->assertStatus(422)
        ->assertJsonFragment(['message' => 'This invite link has already been used.']);
});

it('fails when creator tries to redeem own link', function (): void {
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
        ->postJson("/api/invite-links/{$link->code}/redeem");

    $response->assertStatus(422)
        ->assertJsonFragment(['message' => 'This user is already in the game.']);
});

it('fails when game is no longer pending', function (): void {
    $creator = User::factory()->create();
    $opponent = User::factory()->create();
    $joiner = User::factory()->create();

    $game = Game::factory()->create([
        'status' => GameStatus::Active,
        'tile_bag' => createDefaultTileBag(),
    ]);
    GamePlayer::factory()->create([
        'game_id' => $game->id,
        'user_id' => $creator->id,
        'rack_tiles' => createDefaultRack(),
        'turn_order' => 1,
    ]);
    GamePlayer::factory()->create([
        'game_id' => $game->id,
        'user_id' => $opponent->id,
        'rack_tiles' => createDefaultRack(),
        'turn_order' => 2,
    ]);

    $link = GameInviteLink::create([
        'game_id' => $game->id,
        'inviter_id' => $creator->id,
    ]);

    $response = $this->actingAs($joiner, 'sanctum')
        ->postJson("/api/invite-links/{$link->code}/redeem");

    $response->assertStatus(422)
        ->assertJsonFragment(['message' => 'This game is not accepting new players.']);
});

it('returns 404 for non-existent code', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/invite-links/NONEXIST/redeem');

    $response->assertStatus(404);
});
