<?php

use App\Domain\Game\Actions\InvitePlayerAction;
use App\Domain\Game\Exceptions\GameException;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\GamePlayer;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\Notification;

beforeEach(fn () => Notification::fake());

it('allows inviting multiple players up to the open seats', function (): void {
    $creator = User::factory()->create();
    $game = Game::factory()->pending()->create(['max_players' => 3]);
    GamePlayer::factory()->for($game)->create(['user_id' => $creator->id, 'turn_order' => 1]);

    app(InvitePlayerAction::class)->execute($game->fresh(), User::factory()->create());
    app(InvitePlayerAction::class)->execute($game->fresh(), User::factory()->create());

    expect($game->fresh()->pendingInvitations()->count())->toBe(2);
});

it('refuses to invite beyond the open seats', function (): void {
    $creator = User::factory()->create();
    $game = Game::factory()->pending()->create(['max_players' => 2]);
    GamePlayer::factory()->for($game)->create(['user_id' => $creator->id, 'turn_order' => 1]);

    app(InvitePlayerAction::class)->execute($game->fresh(), User::factory()->create());

    app(InvitePlayerAction::class)->execute($game->fresh(), User::factory()->create());
})->throws(GameException::class);
