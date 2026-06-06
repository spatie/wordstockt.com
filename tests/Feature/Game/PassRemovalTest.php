<?php

use App\Domain\Game\Actions\PassAction;
use App\Domain\Game\Enums\GameStatus;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\GamePlayer;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\Notification;

beforeEach(fn () => Notification::fake());

function threePlayerActiveGame(): array
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

it('removes a player after two of their own consecutive passes in a 3-player game', function (): void {
    [$game, $users] = threePlayerActiveGame();

    // Round 1: each passes once
    app(PassAction::class)->execute($game->fresh(), $users[0]);
    app(PassAction::class)->execute($game->fresh(), $users[1]);
    app(PassAction::class)->execute($game->fresh(), $users[2]);

    // user[0] passes a second consecutive time -> removed
    app(PassAction::class)->execute($game->fresh(), $users[0]);
    $game->refresh();

    expect($game->gamePlayers()->where('user_id', $users[0]->id)->first()->hasLeft())->toBeTrue()
        ->and($game->current_turn_user_id)->toBe($users[1]->id);
});

it('does not remove players in a 2-player game (ends on four passes instead)', function (): void {
    $users = User::factory()->count(2)->create();
    $game = Game::factory()->active()->create([
        'max_players' => 2,
        'current_turn_user_id' => $users[0]->id,
        'tile_bag' => [['letter' => 'A', 'points' => 1]],
    ]);
    $users->each(fn (User $u, int $i) => GamePlayer::factory()->for($game)->create([
        'user_id' => $u->id, 'turn_order' => $i + 1, 'rack_tiles' => [['letter' => 'B', 'points' => 3]],
    ]));

    app(PassAction::class)->execute($game->fresh(), $users[0]);
    app(PassAction::class)->execute($game->fresh(), $users[1]);
    app(PassAction::class)->execute($game->fresh(), $users[0]);
    app(PassAction::class)->execute($game->fresh(), $users[1]);

    $game->refresh();
    expect($game->status)->toBe(GameStatus::Finished)
        ->and($game->gamePlayers()->whereNotNull('left_at')->count())->toBe(0);
});
