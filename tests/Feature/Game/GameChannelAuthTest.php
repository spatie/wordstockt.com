<?php

use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\GamePlayer;
use App\Domain\User\Enums\InvitationStatus;
use App\Domain\User\Models\GameInvitation;
use App\Domain\User\Models\User;

/**
 * canBeWatchedBy backs both the GamePolicy@view check and the private-game
 * broadcast channel authorization, so anyone who can open a game also receives
 * its real-time updates (the bug: invitees were denied the channel and never
 * re-subscribed after accepting).
 */
it('lets a joined player watch the game', function (): void {
    $player = User::factory()->create();
    $game = Game::factory()->pending()->create(['max_players' => 3]);
    GamePlayer::factory()->for($game)->create(['user_id' => $player->id, 'turn_order' => 1]);

    expect($game->fresh()->canBeWatchedBy($player))->toBeTrue();
});

it('lets a pending invitee watch the game (so they get live updates after joining)', function (): void {
    $creator = User::factory()->create();
    $invitee = User::factory()->create();
    $game = Game::factory()->pending()->create(['max_players' => 3]);
    GamePlayer::factory()->for($game)->create(['user_id' => $creator->id, 'turn_order' => 1]);
    GameInvitation::create([
        'game_id' => $game->id,
        'inviter_id' => $creator->id,
        'invitee_id' => $invitee->id,
        'status' => InvitationStatus::Pending,
    ]);

    expect($game->fresh()->canBeWatchedBy($invitee))->toBeTrue();
});

it('lets anyone watch a public pending game', function (): void {
    $creator = User::factory()->create();
    $stranger = User::factory()->create();
    $game = Game::factory()->pending()->create(['max_players' => 3, 'is_public' => true]);
    GamePlayer::factory()->for($game)->create(['user_id' => $creator->id, 'turn_order' => 1]);

    expect($game->fresh()->canBeWatchedBy($stranger))->toBeTrue();
});

it('does not let an unrelated user watch a private game', function (): void {
    $creator = User::factory()->create();
    $stranger = User::factory()->create();
    $game = Game::factory()->pending()->create(['max_players' => 3, 'is_public' => false]);
    GamePlayer::factory()->for($game)->create(['user_id' => $creator->id, 'turn_order' => 1]);

    expect($game->fresh()->canBeWatchedBy($stranger))->toBeFalse();
});
