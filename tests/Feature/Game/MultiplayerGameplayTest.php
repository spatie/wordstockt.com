<?php

use App\Domain\Game\Actions\PassAction;
use App\Domain\Game\Actions\PlayMoveAction;
use App\Domain\Game\Enums\GameStatus;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\GamePlayer;
use App\Domain\Support\Models\Dictionary;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\Notification;

beforeEach(function (): void {
    Notification::fake();
    Dictionary::create(['word' => 'HOI', 'language' => 'nl']);
    Dictionary::create(['word' => 'OH', 'language' => 'nl']);
    Dictionary::create(['word' => 'IK', 'language' => 'nl']);
});

/**
 * @param  array<int, User>  $users
 */
function threePlayerGame(array $users, array $tileBag): Game
{
    $game = Game::factory()->active()->create([
        'language' => 'nl',
        'max_players' => 3,
        'current_turn_user_id' => $users[0]->id,
        'tile_bag' => $tileBag,
    ]);

    foreach ($users as $i => $user) {
        GamePlayer::factory()->for($game)->create([
            'user_id' => $user->id,
            'turn_order' => $i + 1,
            'has_received_blank' => true,
            'rack_tiles' => [],
        ]);
    }

    return $game->fresh();
}

function setRack(Game $game, User $user, array $tiles): void
{
    $game->gamePlayers()->where('user_id', $user->id)->update(['rack_tiles' => $tiles]);
}

it('plays a full 3-player round with turns rotating and scores accumulating', function (): void {
    $users = User::factory()->count(3)->create()->all();
    $game = threePlayerGame($users, array_fill(0, 30, ['letter' => 'A', 'points' => 1, 'is_blank' => false]));

    setRack($game, $users[0], [
        ['letter' => 'H', 'points' => 4, 'is_blank' => false],
        ['letter' => 'O', 'points' => 1, 'is_blank' => false],
        ['letter' => 'I', 'points' => 1, 'is_blank' => false],
    ]);
    setRack($game, $users[1], [['letter' => 'O', 'points' => 1, 'is_blank' => false]]);
    setRack($game, $users[2], [['letter' => 'K', 'points' => 3, 'is_blank' => false]]);

    // Player 1 plays HOI through the centre (horizontal).
    app(PlayMoveAction::class)->execute($game->fresh(), $users[0], [
        ['letter' => 'H', 'points' => 4, 'x' => 6, 'y' => 7, 'is_blank' => false],
        ['letter' => 'O', 'points' => 1, 'x' => 7, 'y' => 7, 'is_blank' => false],
        ['letter' => 'I', 'points' => 1, 'x' => 8, 'y' => 7, 'is_blank' => false],
    ]);

    $game->refresh();
    expect($game->current_turn_user_id)->toBe($users[1]->id)
        ->and($game->gamePlayers()->where('user_id', $users[0]->id)->value('score'))->toBeGreaterThan(0);

    // Player 2 plays OH vertically, reusing the H at (6,7).
    app(PlayMoveAction::class)->execute($game->fresh(), $users[1], [
        ['letter' => 'O', 'points' => 1, 'x' => 6, 'y' => 6, 'is_blank' => false],
    ]);

    $game->refresh();
    expect($game->current_turn_user_id)->toBe($users[2]->id)
        ->and($game->gamePlayers()->where('user_id', $users[1]->id)->value('score'))->toBeGreaterThan(0);

    // Player 3 plays IK vertically, reusing the I at (8,7).
    app(PlayMoveAction::class)->execute($game->fresh(), $users[2], [
        ['letter' => 'K', 'points' => 3, 'x' => 8, 'y' => 8, 'is_blank' => false],
    ]);

    $game->refresh();
    expect($game->current_turn_user_id)->toBe($users[0]->id)
        ->and($game->gamePlayers()->where('user_id', $users[2]->id)->value('score'))->toBeGreaterThan(0)
        ->and($game->status)->toBe(GameStatus::Active);
});

it('ends a 3-player game when a player empties their rack with an empty bag', function (): void {
    $users = User::factory()->count(3)->create()->all();
    $game = threePlayerGame($users, []); // empty bag

    setRack($game, $users[0], [
        ['letter' => 'H', 'points' => 4, 'is_blank' => false],
        ['letter' => 'O', 'points' => 1, 'is_blank' => false],
        ['letter' => 'I', 'points' => 1, 'is_blank' => false],
        ['letter' => 'Q', 'points' => 10, 'is_blank' => false],
    ]);
    setRack($game, $users[1], [
        ['letter' => 'O', 'points' => 1, 'is_blank' => false],
        ['letter' => 'Z', 'points' => 4, 'is_blank' => false],
    ]);
    setRack($game, $users[2], [['letter' => 'K', 'points' => 3, 'is_blank' => false]]);

    app(PlayMoveAction::class)->execute($game->fresh(), $users[0], [
        ['letter' => 'H', 'points' => 4, 'x' => 6, 'y' => 7, 'is_blank' => false],
        ['letter' => 'O', 'points' => 1, 'x' => 7, 'y' => 7, 'is_blank' => false],
        ['letter' => 'I', 'points' => 1, 'x' => 8, 'y' => 7, 'is_blank' => false],
    ]);
    app(PlayMoveAction::class)->execute($game->fresh(), $users[1], [
        ['letter' => 'O', 'points' => 1, 'x' => 6, 'y' => 6, 'is_blank' => false],
    ]);
    // Player 3 empties their rack (bag already empty) -> triggers end game.
    app(PlayMoveAction::class)->execute($game->fresh(), $users[2], [
        ['letter' => 'K', 'points' => 3, 'x' => 8, 'y' => 8, 'is_blank' => false],
    ]);

    $game->refresh();

    // The empty-rack player gets one more turn for the others; pass to close it out if still active.
    if ($game->status === GameStatus::Active) {
        app(PassAction::class)->execute($game->fresh(), User::find($game->current_turn_user_id));
        $game->refresh();
    }

    $player3 = $game->gamePlayers()->where('user_id', $users[2]->id)->first();

    expect($game->status)->toBe(GameStatus::Finished)
        ->and($game->winner_id)->not->toBeNull()
        ->and($player3->received_empty_rack_bonus)->toBeTrue()
        ->and($player3->rack_tiles)->toBe([]);
});
