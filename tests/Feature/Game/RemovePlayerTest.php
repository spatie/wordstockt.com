<?php

use App\Domain\Game\Actions\RemovePlayerAction;
use App\Domain\Game\Enums\GameStatus;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\GamePlayer;
use App\Domain\User\Models\User;

function activeThreePlayerGameForRemoval(): array
{
    $users = User::factory()->count(3)->create();
    $game = Game::factory()->active()->create([
        'max_players' => 3,
        'current_turn_user_id' => $users[0]->id,
        'tile_bag' => [['letter' => 'A', 'points' => 1]],
    ]);
    $users->each(fn (User $u, int $i) => GamePlayer::factory()->for($game)->create([
        'user_id' => $u->id, 'turn_order' => $i + 1, 'rack_tiles' => [['letter' => 'B', 'points' => 3]],
    ]));

    return [$game->fresh(), $users];
}

it('marks the player left, discards their tiles, and advances the turn', function (): void {
    [$game, $users] = activeThreePlayerGameForRemoval();

    app(RemovePlayerAction::class)->execute($game, $users[0], 'removed');
    $game->refresh();

    $removed = $game->gamePlayers()->where('user_id', $users[0]->id)->first();
    expect($removed->hasLeft())->toBeTrue()
        ->and($removed->left_reason)->toBe('removed')
        ->and($removed->rack_tiles)->toBe([])
        ->and($game->status)->toBe(GameStatus::Active)
        ->and($game->current_turn_user_id)->toBe($users[1]->id);
});

it('ends the game when only one active player remains', function (): void {
    [$game, $users] = activeThreePlayerGameForRemoval();

    app(RemovePlayerAction::class)->execute($game->fresh(), $users[0], 'removed');
    app(RemovePlayerAction::class)->execute($game->fresh(), $users[1], 'removed');
    $game->refresh();

    expect($game->status)->toBe(GameStatus::Finished)
        ->and($game->winner_id)->toBe($users[2]->id);
});
