<?php

use App\Domain\Game\Enums\GameStatus;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\GamePlayer;
use App\Domain\Game\Notifications\GameInviteAcceptedNotification;
use App\Domain\User\Enums\InvitationStatus;
use App\Domain\User\Events\GameInvitationAcceptedEvent;
use App\Domain\User\Models\GameInvitation;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;

it('accepts invitation and joins the game', function (): void {
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

    $invitation = GameInvitation::create([
        'game_id' => $game->id,
        'inviter_id' => $creator->id,
        'invitee_id' => $invitee->id,
        'status' => InvitationStatus::Pending,
    ]);

    $response = $this->actingAs($invitee, 'sanctum')
        ->postJson("/api/invitations/{$invitation->ulid}/accept");

    $response->assertOk()
        ->assertJsonPath('data.status', GameStatus::Active->value);

    expect($invitation->fresh()->status)->toBe(InvitationStatus::Accepted);
    expect($game->fresh()->hasPlayer($invitee))->toBeTrue();
    expect($game->fresh()->status)->toBe(GameStatus::Active);
});

it('notifies inviter when invitation is accepted', function (): void {
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

    $invitation = GameInvitation::create([
        'game_id' => $game->id,
        'inviter_id' => $creator->id,
        'invitee_id' => $invitee->id,
        'status' => InvitationStatus::Pending,
    ]);

    $this->actingAs($invitee, 'sanctum')
        ->postJson("/api/invitations/{$invitation->ulid}/accept");

    Notification::assertSentTo($creator, GameInviteAcceptedNotification::class);
});

it('fails when accepting invitation for another user', function (): void {
    $creator = User::factory()->create();
    $invitee = User::factory()->create();
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

    $invitation = GameInvitation::create([
        'game_id' => $game->id,
        'inviter_id' => $creator->id,
        'invitee_id' => $invitee->id,
        'status' => InvitationStatus::Pending,
    ]);

    $response = $this->actingAs($otherUser, 'sanctum')
        ->postJson("/api/invitations/{$invitation->ulid}/accept");

    $response->assertStatus(403);
});

it('fails when accepting already accepted invitation', function (): void {
    $creator = User::factory()->create();
    $invitee = User::factory()->create();

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

    $invitation = GameInvitation::create([
        'game_id' => $game->id,
        'inviter_id' => $creator->id,
        'invitee_id' => $invitee->id,
        'status' => InvitationStatus::Accepted,
    ]);

    $response = $this->actingAs($invitee, 'sanctum')
        ->postJson("/api/invitations/{$invitation->ulid}/accept");

    $response->assertStatus(422)
        ->assertJsonFragment(['message' => 'This invitation is no longer pending.']);
});

it('fails when accepting declined invitation', function (): void {
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

    $invitation = GameInvitation::create([
        'game_id' => $game->id,
        'inviter_id' => $creator->id,
        'invitee_id' => $invitee->id,
        'status' => InvitationStatus::Declined,
    ]);

    $response = $this->actingAs($invitee, 'sanctum')
        ->postJson("/api/invitations/{$invitation->ulid}/accept");

    $response->assertStatus(422);
});

it('broadcasts event when invitation is accepted', function (): void {
    Event::fake([GameInvitationAcceptedEvent::class]);

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

    $invitation = GameInvitation::create([
        'game_id' => $game->id,
        'inviter_id' => $creator->id,
        'invitee_id' => $invitee->id,
        'status' => InvitationStatus::Pending,
    ]);

    $this->actingAs($invitee, 'sanctum')
        ->postJson("/api/invitations/{$invitation->ulid}/accept");

    Event::assertDispatched(GameInvitationAcceptedEvent::class, function ($event) use ($game, $creator, $invitee) {
        return $event->game->id === $game->id
            && $event->inviter->id === $creator->id
            && $event->accepter->id === $invitee->id;
    });
});
