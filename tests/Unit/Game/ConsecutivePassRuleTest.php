<?php

use App\Domain\Game\Enums\MoveType;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\GamePlayer;
use App\Domain\Game\Models\Move;
use App\Domain\Game\Support\Rules\EndGame\ConsecutivePassRule;
use App\Domain\User\Models\User;

it('never ends a 3-player game on consecutive passes (removal handles it)', function (): void {
    $game = Game::factory()->active()->create(['max_players' => 3]);
    User::factory()->count(3)->create()->each(
        fn (User $u, int $i) => GamePlayer::factory()->for($game)->create(['user_id' => $u->id, 'turn_order' => $i + 1])
    );
    foreach (range(1, 6) as $n) {
        Move::create(['game_id' => $game->id, 'user_id' => $game->players()->first()->id, 'type' => MoveType::Pass, 'score' => 0, 'tiles' => null, 'words' => null]);
    }

    expect((new ConsecutivePassRule)->shouldEndGame($game->fresh()))->toBeFalse();
});
