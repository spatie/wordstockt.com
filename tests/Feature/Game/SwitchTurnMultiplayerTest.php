<?php

use App\Domain\Game\Actions\SwitchTurnAction;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\GamePlayer;
use App\Domain\User\Models\User;

function activeThreePlayerGame(): array
{
    $users = User::factory()->count(3)->create();
    $game = Game::factory()->active()->create([
        'max_players' => 3,
        'current_turn_user_id' => $users[0]->id,
    ]);
    $users->each(fn (User $u, int $i) => GamePlayer::factory()->for($game)->create([
        'user_id' => $u->id, 'turn_order' => $i + 1,
    ]));

    return [$game->fresh(), $users];
}

it('advances in turn order and wraps around', function (): void {
    [$game, $users] = activeThreePlayerGame();

    app(SwitchTurnAction::class)->execute($game);
    expect($game->fresh()->current_turn_user_id)->toBe($users[1]->id);

    app(SwitchTurnAction::class)->execute($game->fresh());
    expect($game->fresh()->current_turn_user_id)->toBe($users[2]->id);

    app(SwitchTurnAction::class)->execute($game->fresh());
    expect($game->fresh()->current_turn_user_id)->toBe($users[0]->id);
});

it('skips a player who has left', function (): void {
    [$game, $users] = activeThreePlayerGame();
    $game->gamePlayers()->where('user_id', $users[1]->id)->update(['left_at' => now()]);

    app(SwitchTurnAction::class)->execute($game->fresh());

    expect($game->fresh()->current_turn_user_id)->toBe($users[2]->id);
});
