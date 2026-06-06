<?php

use App\Domain\Game\Actions\Stats\UpdateGameEndStatsAction;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\GamePlayer;
use App\Domain\Game\Models\HeadToHeadStats;
use App\Domain\User\Models\EloHistory;
use App\Domain\User\Models\User;
use App\Domain\User\Models\UserStatistics;
use Illuminate\Support\Collection;

/**
 * @param  array<int, int>  $scores
 * @return array{0: Game, 1: Collection<int, User>}
 */
function finishedThreePlayerGame(array $scores): array
{
    $users = collect($scores)->map(fn (): User => User::factory()->create(['elo_rating' => 1500]));
    $game = Game::factory()->finished()->create(['max_players' => 3]);

    $users->each(fn (User $u, int $i) => GamePlayer::factory()->for($game)->create([
        'user_id' => $u->id,
        'turn_order' => $i + 1,
        'score' => array_values($scores)[$i],
        'rack_tiles' => [],
    ]));

    $winnerIndex = collect($scores)->keys()->sortByDesc(fn ($k) => $scores[$k])->first();
    $game->update(['winner_id' => $users[$winnerIndex]->id]);

    return [$game->fresh(['gamePlayers.user']), $users];
}

it('records elo history and head-to-head for every pair in a 3-player game', function (): void {
    [$game, $users] = finishedThreePlayerGame([200, 150, 100]);

    app(UpdateGameEndStatsAction::class)->execute($game);

    expect(EloHistory::where('game_id', $game->id)->count())->toBe(3);
    expect(HeadToHeadStats::count())->toBe(6);
    expect(UserStatistics::where('user_id', $users[0]->id)->value('games_won'))->toBe(1)
        ->and(UserStatistics::where('user_id', $users[2]->id)->value('games_lost'))->toBe(1);
});

it('still uses single-pair logic for two-player games', function (): void {
    $users = User::factory()->count(2)->create(['elo_rating' => 1500]);
    $game = Game::factory()->finished()->create(['max_players' => 2, 'winner_id' => $users[0]->id]);
    $users->each(fn (User $u, int $i) => GamePlayer::factory()->for($game)->create([
        'user_id' => $u->id,
        'turn_order' => $i + 1,
        'score' => 150 - $i * 50,
    ]));

    app(UpdateGameEndStatsAction::class)->execute($game->fresh(['gamePlayers.user']));

    expect(EloHistory::where('game_id', $game->id)->count())->toBe(2);
});
