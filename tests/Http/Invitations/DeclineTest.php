<?php

use App\Domain\Game\Enums\GameStatus;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\GamePlayer;
use App\Domain\User\Enums\InvitationStatus;
use App\Domain\User\Events\GameInvitationDeclinedEvent;
use App\Domain\User\Models\GameInvitation;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\Event;

it('declines invitation', function (): void {
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
        ->postJson("/api/invitations/{$invitation->ulid}/decline");

    $response->assertNoContent();

    expect($invitation->fresh()->status)->toBe(InvitationStatus::Declined);
    expect($game->fresh()->hasPlayer($invitee))->toBeFalse();
    expect($game->fresh()->status)->toBe(GameStatus::Pending);
});

it('fails when declining invitation for another user', function (): void {
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
        ->postJson("/api/invitations/{$invitation->ulid}/decline");

    $response->assertStatus(403);
});

it('fails when declining already declined invitation', function (): void {
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
        ->postJson("/api/invitations/{$invitation->ulid}/decline");

    $response->assertStatus(422);
});

it('broadcasts event when invitation is declined', function (): void {
    Event::fake([GameInvitationDeclinedEvent::class]);

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
        ->postJson("/api/invitations/{$invitation->ulid}/decline");

    Event::assertDispatched(GameInvitationDeclinedEvent::class, function ($event) use ($invitation) {
        return $event->invitation->id === $invitation->id;
    });
});
