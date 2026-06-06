<?php

use App\Domain\Game\Actions\PlayMoveAction;
use App\Domain\Game\Actions\SwapTilesAction;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\GamePlayer;
use App\Domain\Support\Models\Dictionary;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\Notification;

beforeEach(fn () => Notification::fake());

it('resets a player consecutive pass counter when they swap', function (): void {
    $users = User::factory()->count(3)->create();
    $game = Game::factory()->active()->create([
        'max_players' => 3,
        'current_turn_user_id' => $users[0]->id,
        'tile_bag' => array_fill(0, 10, ['letter' => 'A', 'points' => 1]),
    ]);
    $users->each(fn (User $u, int $i) => GamePlayer::factory()->for($game)->create([
        'user_id' => $u->id, 'turn_order' => $i + 1, 'rack_tiles' => [['letter' => 'B', 'points' => 3]],
    ]));

    $game->gamePlayers()->where('user_id', $users[0]->id)->update(['consecutive_passes' => 1]);

    app(SwapTilesAction::class)
        ->execute($game->fresh(), $users[0], [['letter' => 'B', 'points' => 3]]);

    expect($game->gamePlayers()->where('user_id', $users[0]->id)->value('consecutive_passes'))->toBe(0);
});

it('resets a player consecutive pass counter when they play a move', function (): void {
    Dictionary::create(['word' => 'HOI', 'language' => 'nl']);

    $users = User::factory()->count(3)->create();
    $game = Game::factory()->active()->create([
        'language' => 'nl',
        'max_players' => 3,
        'current_turn_user_id' => $users[0]->id,
        'tile_bag' => array_fill(0, 10, ['letter' => 'A', 'points' => 1]),
    ]);
    $users->each(fn (User $u, int $i) => GamePlayer::factory()->for($game)->create([
        'user_id' => $u->id, 'turn_order' => $i + 1, 'rack_tiles' => [['letter' => 'B', 'points' => 3]],
    ]));

    $game->gamePlayers()->where('user_id', $users[0]->id)->update([
        'consecutive_passes' => 1,
        'has_received_blank' => true,
        'rack_tiles' => [
            ['letter' => 'H', 'points' => 4, 'is_blank' => false],
            ['letter' => 'O', 'points' => 1, 'is_blank' => false],
            ['letter' => 'I', 'points' => 4, 'is_blank' => false],
        ],
    ]);

    app(PlayMoveAction::class)
        ->execute($game->fresh(), $users[0], [
            ['letter' => 'H', 'points' => 4, 'x' => 6, 'y' => 7, 'is_blank' => false],
            ['letter' => 'O', 'points' => 1, 'x' => 7, 'y' => 7, 'is_blank' => false],
            ['letter' => 'I', 'points' => 4, 'x' => 8, 'y' => 7, 'is_blank' => false],
        ]);

    expect($game->gamePlayers()->where('user_id', $users[0]->id)->value('consecutive_passes'))->toBe(0);
});
