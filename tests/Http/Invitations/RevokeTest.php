<?php

use App\Domain\Game\Enums\GameStatus;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\GamePlayer;
use App\Domain\User\Enums\InvitationStatus;
use App\Domain\User\Models\GameInvitation;
use App\Domain\User\Models\User;

it('revokes pending invitation', function (): void {
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

    $response = $this->actingAs($creator, 'sanctum')
        ->deleteJson("/api/invitations/{$invitation->ulid}");

    $response->assertNoContent();

    expect(GameInvitation::find($invitation->id))->toBeNull();
});

it('fails when revoking invitation created by another user', function (): void {
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
        ->deleteJson("/api/invitations/{$invitation->ulid}");

    $response->assertForbidden();
});

it('fails when revoking non-pending invitation', function (): void {
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

    $response = $this->actingAs($creator, 'sanctum')
        ->deleteJson("/api/invitations/{$invitation->ulid}");

    $response->assertStatus(422);
});
