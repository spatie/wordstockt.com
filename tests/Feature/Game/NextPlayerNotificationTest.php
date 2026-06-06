<?php

use App\Domain\Game\Actions\PassAction;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\GamePlayer;
use App\Domain\Game\Notifications\YourTurnNotification;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\Notification;

beforeEach(fn () => Notification::fake());

function threePlayerNotificationGame(): array
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

it('notifies the actual next player when the turn moves in a 3-player game', function (): void {
    [$game, $users] = threePlayerNotificationGame();

    app(PassAction::class)->execute($game->fresh(), $users[0]);

    Notification::assertSentTo($users[1], YourTurnNotification::class);
    Notification::assertNotSentTo($users[2], YourTurnNotification::class);
    Notification::assertNotSentTo($users[0], YourTurnNotification::class);
});

it('notifies player[2] (not player[0]) when player[1] moves and the turn advances', function (): void {
    [$game, $users] = threePlayerNotificationGame();

    // Move turn to player[1] first.
    $game->update(['current_turn_user_id' => $users[1]->id]);

    app(PassAction::class)->execute($game->fresh(), $users[1]);

    Notification::assertSentTo($users[2], YourTurnNotification::class);
    Notification::assertNotSentTo($users[0], YourTurnNotification::class);
    Notification::assertNotSentTo($users[1], YourTurnNotification::class);
});
