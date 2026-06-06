<?php

use App\Domain\Game\Actions\ActivateGameAction;
use App\Domain\Game\Enums\GameStatus;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\GamePlayer;
use App\Domain\Game\Notifications\YourTurnNotification;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\Notification;

it('activates a pending game and assigns the turn to a player in the game', function (): void {
    Notification::fake();

    $game = Game::factory()->pending()->create(['max_players' => 3]);
    $users = User::factory()->count(3)->create();
    $users->each(fn (User $u, int $i) => GamePlayer::factory()->for($game)->create([
        'user_id' => $u->id,
        'turn_order' => $i + 1,
    ]));

    app(ActivateGameAction::class)->execute($game->fresh());

    $game->refresh();
    expect($game->status)->toBe(GameStatus::Active)
        ->and($game->current_turn_user_id)->toBeIn($users->pluck('id')->all())
        ->and($game->turn_expires_at)->not->toBeNull();

    Notification::assertSentTo(
        $users->firstWhere('id', $game->current_turn_user_id),
        YourTurnNotification::class
    );
});
