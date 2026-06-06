<?php

use App\Domain\Game\Actions\ResignAction;
use App\Domain\Game\Enums\GameStatus;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\GamePlayer;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\Notification;

beforeEach(fn () => Notification::fake());

it('removes a resigning player in a 3-player game and continues', function (): void {
    $users = User::factory()->count(3)->create();
    $game = Game::factory()->active()->create([
        'max_players' => 3, 'current_turn_user_id' => $users[0]->id, 'tile_bag' => [['letter' => 'A', 'points' => 1]],
    ]);
    $users->each(fn (User $u, int $i) => GamePlayer::factory()->for($game)->create([
        'user_id' => $u->id, 'turn_order' => $i + 1, 'rack_tiles' => [['letter' => 'B', 'points' => 3]],
    ]));

    app(ResignAction::class)->execute($game->fresh(), $users[0]);
    $game->refresh();

    expect($game->status)->toBe(GameStatus::Active)
        ->and($game->gamePlayers()->where('user_id', $users[0]->id)->first()->left_reason)->toBe('resigned')
        ->and($game->current_turn_user_id)->toBe($users[1]->id);
});

it('keeps two-player resignation behavior (opponent wins immediately)', function (): void {
    $users = User::factory()->count(2)->create();
    $game = Game::factory()->active()->create([
        'max_players' => 2, 'current_turn_user_id' => $users[0]->id,
    ]);
    $users->each(fn (User $u, int $i) => GamePlayer::factory()->for($game)->create(['user_id' => $u->id, 'turn_order' => $i + 1]));

    app(ResignAction::class)->execute($game->fresh(), $users[0]);
    $game->refresh();

    expect($game->status)->toBe(GameStatus::Finished)
        ->and($game->winner_id)->toBe($users[1]->id);
});
