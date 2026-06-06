<?php

use App\Domain\Game\Actions\EndGameAction;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\GamePlayer;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\Notification;

beforeEach(fn () => Notification::fake());

it('picks the highest-scoring active player as winner, ignoring left players', function (): void {
    $users = User::factory()->count(3)->create();
    $game = Game::factory()->active()->create(['max_players' => 3, 'tile_bag' => []]);
    GamePlayer::factory()->for($game)->create(['user_id' => $users[0]->id, 'turn_order' => 1, 'score' => 100, 'rack_tiles' => []]);
    GamePlayer::factory()->for($game)->create(['user_id' => $users[1]->id, 'turn_order' => 2, 'score' => 150, 'rack_tiles' => []]);
    GamePlayer::factory()->for($game)->left()->create(['user_id' => $users[2]->id, 'turn_order' => 3, 'score' => 999, 'rack_tiles' => []]);

    app(EndGameAction::class)->execute($game);

    expect($game->fresh()->winner_id)->toBe($users[1]->id);
});

it('records a draw when the top active score is tied', function (): void {
    $users = User::factory()->count(3)->create();
    $game = Game::factory()->active()->create(['max_players' => 3, 'tile_bag' => []]);
    GamePlayer::factory()->for($game)->create(['user_id' => $users[0]->id, 'turn_order' => 1, 'score' => 150, 'rack_tiles' => []]);
    GamePlayer::factory()->for($game)->create(['user_id' => $users[1]->id, 'turn_order' => 2, 'score' => 150, 'rack_tiles' => []]);
    GamePlayer::factory()->for($game)->create(['user_id' => $users[2]->id, 'turn_order' => 3, 'score' => 90, 'rack_tiles' => []]);

    app(EndGameAction::class)->execute($game);

    expect($game->fresh()->winner_id)->toBeNull();
});
