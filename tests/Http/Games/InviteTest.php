<?php

use App\Domain\Game\Enums\GameStatus;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\GamePlayer;
use App\Domain\Game\Notifications\GameInvitationNotification;
use App\Domain\User\Enums\InvitationStatus;
use App\Domain\User\Models\GameInvitation;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\Notification;

it('creates a pending invitation when inviting a user', function (): void {
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

    $response = $this->actingAs($creator, 'sanctum')
        ->postJson("/api/games/{$game->ulid}/invite", [
            'user_ulid' => $invitee->ulid,
        ]);

    $response->assertCreated()
        ->assertJsonPath('data.status', InvitationStatus::Pending->value)
        ->assertJsonPath('data.invitee.ulid', $invitee->ulid);

    expect(GameInvitation::where('invitee_id', $invitee->id)->exists())->toBeTrue();
    expect($game->fresh()->status)->toBe(GameStatus::Pending);
});

it('sends notification to invitee', function (): void {
    Notification::fake();

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

    $this->actingAs($creator, 'sanctum')
        ->postJson("/api/games/{$game->ulid}/invite", [
            'user_ulid' => $invitee->ulid,
        ]);

    Notification::assertSentTo($invitee, GameInvitationNotification::class);
});

it('fails when inviting yourself', function (): void {
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
        ->postJson("/api/games/{$game->ulid}/invite", [
            'user_ulid' => $creator->ulid,
        ]);

    $response->assertStatus(422);
});

it('fails when user already has pending invitation', function (): void {
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
        ->postJson("/api/games/{$game->ulid}/invite", [
            'user_ulid' => $invitee->ulid,
        ]);

    $response->assertStatus(422)
        ->assertJsonFragment(['message' => 'This user already has a pending invitation. Wait for them to respond.']);
});

it('allows re-inviting after declined invitation', function (): void {
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
        'status' => InvitationStatus::Declined,
    ]);

    $response = $this->actingAs($creator, 'sanctum')
        ->postJson("/api/games/{$game->ulid}/invite", [
            'user_ulid' => $invitee->ulid,
        ]);

    $response->assertOk(); // OK (not Created) because we're reusing the existing declined invitation
    expect(GameInvitation::where('invitee_id', $invitee->id)->pending()->count())->toBe(1);
});

it('fails when non-creator tries to invite', function (): void {
    $creator = User::factory()->create();
    $otherUser = User::factory()->create();
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

    $response = $this->actingAs($otherUser, 'sanctum')
        ->postJson("/api/games/{$game->ulid}/invite", [
            'user_ulid' => $invitee->ulid,
        ]);

    $response->assertStatus(403);
});

it('fails when user does not exist', function (): void {
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
        ->postJson("/api/games/{$game->ulid}/invite", [
            'user_ulid' => '01h1234567890abcdefghjkmnp',
        ]);

    $response->assertStatus(422);
});

it('returns 401 for unauthenticated request', function (): void {
    $game = Game::factory()->create(['status' => GameStatus::Pending]);

    $response = $this->postJson("/api/games/{$game->ulid}/invite", [
        'user_ulid' => '01h1234567890abcdefghjkmnp',
    ]);

    $response->assertStatus(401);
});
