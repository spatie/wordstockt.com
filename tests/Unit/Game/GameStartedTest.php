<?php

use App\Domain\Game\Actions\ActivateGameAction;
use App\Domain\Game\Events\GameStarted;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\GamePlayer;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;

it('broadcasts on the game channel as game.started', function (): void {
    $game = Game::factory()->active()->create();

    $event = new GameStarted($game);

    expect(collect($event->broadcastOn())->map(fn ($c) => $c->name)->all())
        ->toBe(['private-game.'.$game->ulid])
        ->and($event->broadcastAs())->toBe('game.started');
});

it('fires GameStarted when a game is activated', function (): void {
    Notification::fake();
    Event::fake([GameStarted::class]);

    $game = Game::factory()->pending()->create(['max_players' => 3]);
    User::factory()->count(3)->create()->each(
        fn (User $u, int $i) => GamePlayer::factory()->for($game)->create([
            'user_id' => $u->id, 'turn_order' => $i + 1,
        ])
    );

    app(ActivateGameAction::class)->execute($game->fresh());

    Event::assertDispatched(GameStarted::class);
});
