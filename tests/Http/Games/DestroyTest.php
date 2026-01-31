<?php

use App\Domain\Game\Enums\GameStatus;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\GamePlayer;
use App\Domain\User\Models\GameInvitation;
use App\Domain\User\Models\User;

it('allows creator to delete a pending game', function (): void {
    $creator = User::factory()->create();
    $game = createPendingGameWithCreator($creator);

    $response = $this->actingAs($creator, 'sanctum')
        ->deleteJson("/api/games/{$game->ulid}");

    $response->assertOk()
        ->assertJsonPath('message', 'Game deleted successfully.');

    expect(Game::find($game->id))->toBeNull();
});

it('deletes associated game players when deleting game', function (): void {
    $creator = User::factory()->create();
    $game = createPendingGameWithCreator($creator);

    expect(GamePlayer::where('game_id', $game->id)->count())->toBe(1);

    $this->actingAs($creator, 'sanctum')
        ->deleteJson("/api/games/{$game->ulid}");

    expect(GamePlayer::where('game_id', $game->id)->count())->toBe(0);
});

it('deletes associated game invitations when deleting game', function (): void {
    $creator = User::factory()->create();
    $invitee = User::factory()->create();
    $game = createPendingGameWithCreator($creator);

    GameInvitation::create([
        'game_id' => $game->id,
        'inviter_id' => $creator->id,
        'invitee_id' => $invitee->id,
        'status' => 'pending',
    ]);

    expect(GameInvitation::where('game_id', $game->id)->count())->toBe(1);

    $this->actingAs($creator, 'sanctum')
        ->deleteJson("/api/games/{$game->ulid}");

    expect(GameInvitation::where('game_id', $game->id)->count())->toBe(0);
});

it('rejects delete by non-creator', function (): void {
    $creator = User::factory()->create();
    $otherUser = User::factory()->create();
    $game = createPendingGameWithCreator($creator);

    $response = $this->actingAs($otherUser, 'sanctum')
        ->deleteJson("/api/games/{$game->ulid}");

    $response->assertStatus(403)
        ->assertJsonPath('message', 'Only the game creator can perform this action.');

    expect(Game::find($game->id))->not->toBeNull();
});

it('rejects delete by player who joined but is not creator', function (): void {
    $creator = User::factory()->create();
    $player2 = User::factory()->create();
    $game = createGameWithPlayers(player1: $creator, player2: $player2, status: GameStatus::Pending);

    $response = $this->actingAs($player2, 'sanctum')
        ->deleteJson("/api/games/{$game->ulid}");

    $response->assertStatus(403)
        ->assertJsonPath('message', 'Only the game creator can perform this action.');
});

it('rejects delete for active game', function (): void {
    $creator = User::factory()->create();
    $game = createGameWithPlayers(player1: $creator, status: GameStatus::Active);

    $response = $this->actingAs($creator, 'sanctum')
        ->deleteJson("/api/games/{$game->ulid}");

    $response->assertStatus(422)
        ->assertJsonPath('message', 'Cannot delete a game that has already started.');

    expect(Game::find($game->id))->not->toBeNull();
});

it('rejects delete for finished game', function (): void {
    $creator = User::factory()->create();
    $game = createGameWithPlayers(player1: $creator, status: GameStatus::Finished);

    $response = $this->actingAs($creator, 'sanctum')
        ->deleteJson("/api/games/{$game->ulid}");

    $response->assertStatus(422)
        ->assertJsonPath('message', 'Cannot delete a game that has already started.');
});

it('returns 401 for unauthenticated request', function (): void {
    $game = createPendingGameWithCreator();

    $response = $this->deleteJson("/api/games/{$game->ulid}");

    $response->assertStatus(401);
});

it('returns 404 for non-existent game', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->deleteJson('/api/games/non-existent-ulid');

    $response->assertStatus(404);
});

/**
 * Create a pending game with only the creator (no opponent yet).
 */
function createPendingGameWithCreator(?User $creator = null): Game
{
    $creator ??= User::factory()->create();

    $game = Game::factory()->create([
        'status' => GameStatus::Pending,
        'language' => 'en',
        'current_turn_user_id' => null,
        'board_state' => createEmptyBoard(),
        'tile_bag' => createDefaultTileBag(),
    ]);

    GamePlayer::factory()->create([
        'game_id' => $game->id,
        'user_id' => $creator->id,
        'turn_order' => 1,
        'rack_tiles' => createDefaultRack(),
        'score' => 0,
    ]);

    return $game->fresh(['players', 'gamePlayers']);
}
