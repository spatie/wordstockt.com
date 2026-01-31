<?php

use App\Domain\Game\Enums\GameStatus;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\GamePlayer;
use App\Domain\User\Enums\InvitationStatus;
use App\Domain\User\Models\GameInvitation;
use App\Domain\User\Models\User;

it('lists pending invitations for user', function (): void {
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
        ->getJson('/api/invitations');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.ulid', $invitation->ulid)
        ->assertJsonPath('data.0.status', InvitationStatus::Pending->value);
});

it('excludes accepted and declined invitations', function (): void {
    $creator = User::factory()->create();
    $invitee = User::factory()->create();

    $game1 = Game::factory()->create(['status' => GameStatus::Pending, 'tile_bag' => createDefaultTileBag()]);
    $game2 = Game::factory()->create(['status' => GameStatus::Active, 'tile_bag' => createDefaultTileBag()]);
    $game3 = Game::factory()->create(['status' => GameStatus::Pending, 'tile_bag' => createDefaultTileBag()]);

    foreach ([$game1, $game2, $game3] as $game) {
        GamePlayer::factory()->create([
            'game_id' => $game->id,
            'user_id' => $creator->id,
            'rack_tiles' => createDefaultRack(),
            'turn_order' => 1,
        ]);
    }

    GameInvitation::create([
        'game_id' => $game1->id,
        'inviter_id' => $creator->id,
        'invitee_id' => $invitee->id,
        'status' => InvitationStatus::Pending,
    ]);

    GameInvitation::create([
        'game_id' => $game2->id,
        'inviter_id' => $creator->id,
        'invitee_id' => $invitee->id,
        'status' => InvitationStatus::Accepted,
    ]);

    GameInvitation::create([
        'game_id' => $game3->id,
        'inviter_id' => $creator->id,
        'invitee_id' => $invitee->id,
        'status' => InvitationStatus::Declined,
    ]);

    $response = $this->actingAs($invitee, 'sanctum')
        ->getJson('/api/invitations');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.game.ulid', $game1->ulid);
});

it('returns empty list when no pending invitations', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/invitations');

    $response->assertOk()
        ->assertJsonCount(0, 'data');
});
