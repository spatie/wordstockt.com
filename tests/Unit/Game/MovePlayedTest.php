<?php

use App\Domain\Game\Enums\MoveType;
use App\Domain\Game\Events\MovePlayed;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\GamePlayer;
use App\Domain\Game\Models\Move;
use App\Domain\User\Models\User;

it('broadcasts on the game channel and every other players user channel', function (): void {
    $users = User::factory()->count(3)->create();
    $game = Game::factory()->active()->create(['max_players' => 3]);
    $users->each(fn (User $u, int $i) => GamePlayer::factory()->for($game)->create(['user_id' => $u->id, 'turn_order' => $i + 1]));
    $game->load('gamePlayers.user');

    $move = Move::create(['game_id' => $game->id, 'user_id' => $users[0]->id, 'type' => MoveType::Pass, 'score' => 0, 'tiles' => null, 'words' => null]);

    $channels = collect((new MovePlayed($game, $move, $users[0]))->broadcastOn())
        ->map(fn ($c) => $c->name)
        ->all();

    expect($channels)->toContain('private-game.'.$game->ulid)
        ->and($channels)->toContain('private-user.'.$users[1]->ulid)
        ->and($channels)->toContain('private-user.'.$users[2]->ulid)
        ->and($channels)->not->toContain('private-user.'.$users[0]->ulid);
});
