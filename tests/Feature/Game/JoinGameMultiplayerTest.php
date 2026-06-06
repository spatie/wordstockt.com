<?php

use App\Domain\Game\Actions\JoinGameAction;
use App\Domain\Game\Enums\GameStatus;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\GamePlayer;
use App\Domain\Game\Support\TileBag;
use App\Domain\User\Models\User;

beforeEach(function (): void {
    $this->creator = User::factory()->create();
});

function pendingGameWithCreator(User $creator, int $maxPlayers): Game
{
    $game = Game::factory()->pending()->create([
        'max_players' => $maxPlayers,
        'tile_bag' => TileBag::forLanguage('en')->toArray(),
    ]);
    GamePlayer::factory()->for($game)->create(['user_id' => $creator->id, 'turn_order' => 1]);

    return $game->fresh();
}

it('keeps a 3-player game pending until all seats fill and assigns sequential turn order', function (): void {
    $game = pendingGameWithCreator($this->creator, 3);
    $second = User::factory()->create();

    app(JoinGameAction::class)->execute($game, $second);
    $game->refresh();

    expect($game->status)->toBe(GameStatus::Pending)
        ->and($game->gamePlayers()->where('user_id', $second->id)->value('turn_order'))->toBe(2);

    $third = User::factory()->create();
    app(JoinGameAction::class)->execute($game->fresh(), $third);
    $game->refresh();

    expect($game->status)->toBe(GameStatus::Active)
        ->and($game->gamePlayers()->where('user_id', $third->id)->value('turn_order'))->toBe(3);
});

it('activates a 2-player game as soon as the second player joins', function (): void {
    $game = pendingGameWithCreator($this->creator, 2);
    app(JoinGameAction::class)->execute($game, User::factory()->create());

    expect($game->fresh()->status)->toBe(GameStatus::Active);
});
