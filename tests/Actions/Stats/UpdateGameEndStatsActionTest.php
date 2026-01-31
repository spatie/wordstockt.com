<?php

use App\Domain\Game\Actions\Stats\UpdateGameEndStatsAction;
use App\Domain\Game\Models\GamePlayer;
use App\Domain\Game\Models\HeadToHeadStats;
use App\Domain\Game\Models\Move;
use App\Domain\User\Models\EloHistory;
use App\Domain\User\Models\User;
use App\Domain\User\Models\UserStatistics;

beforeEach(function (): void {
    $this->action = app(UpdateGameEndStatsAction::class);
    $this->player1 = User::factory()->create(['elo_rating' => 1200]);
    $this->player2 = User::factory()->create(['elo_rating' => 1200]);
    $this->game = createGameWithPlayers(player1: $this->player1, player2: $this->player2);
});

it('creates user statistics for both players', function (): void {
    $this->game->update(['winner_id' => $this->player1->id]);

    $this->action->execute($this->game);

    expect(UserStatistics::where('user_id', $this->player1->id)->exists())->toBeTrue();
    expect(UserStatistics::where('user_id', $this->player2->id)->exists())->toBeTrue();
});

it('updates games won for winner', function (): void {
    $this->game->update(['winner_id' => $this->player1->id]);

    $this->action->execute($this->game);

    $stats = UserStatistics::where('user_id', $this->player1->id)->first();
    expect($stats->games_won)->toBe(1);
});

it('updates games lost for loser', function (): void {
    $this->game->update(['winner_id' => $this->player1->id]);

    $this->action->execute($this->game);

    $stats = UserStatistics::where('user_id', $this->player2->id)->first();
    expect($stats->games_lost)->toBe(1);
});

it('updates games draw for both players when no winner', function (): void {
    $this->game->update(['winner_id' => null]);

    $this->action->execute($this->game);

    $stats1 = UserStatistics::where('user_id', $this->player1->id)->first();
    $stats2 = UserStatistics::where('user_id', $this->player2->id)->first();

    expect($stats1->games_draw)->toBe(1);
    expect($stats2->games_draw)->toBe(1);
});

it('updates total game score', function (): void {
    GamePlayer::where('game_id', $this->game->id)
        ->where('user_id', $this->player1->id)
        ->update(['score' => 150]);

    GamePlayer::where('game_id', $this->game->id)
        ->where('user_id', $this->player2->id)
        ->update(['score' => 120]);

    $this->game->update(['winner_id' => $this->player1->id]);

    $this->action->execute($this->game->fresh());

    $stats1 = UserStatistics::where('user_id', $this->player1->id)->first();
    $stats2 = UserStatistics::where('user_id', $this->player2->id)->first();

    expect($stats1->total_game_score)->toBe(150);
    expect($stats2->total_game_score)->toBe(120);
});

it('updates highest game score', function (): void {
    GamePlayer::where('game_id', $this->game->id)
        ->where('user_id', $this->player1->id)
        ->update(['score' => 200]);

    $this->game->update(['winner_id' => $this->player1->id]);

    $this->action->execute($this->game->fresh());

    $stats = UserStatistics::where('user_id', $this->player1->id)->first();
    expect($stats->highest_game_score)->toBe(200);
});

it('increments win streak on win', function (): void {
    $this->game->update(['winner_id' => $this->player1->id]);

    $this->action->execute($this->game);

    $stats = UserStatistics::where('user_id', $this->player1->id)->first();
    expect($stats->current_win_streak)->toBe(1);
});

it('resets win streak on loss', function (): void {
    UserStatistics::create([
        'user_id' => $this->player2->id,
        'current_win_streak' => 5,
    ]);

    $this->game->update(['winner_id' => $this->player1->id]);

    $this->action->execute($this->game);

    $stats = UserStatistics::where('user_id', $this->player2->id)->first();
    expect($stats->current_win_streak)->toBe(0);
});

it('updates best win streak', function (): void {
    UserStatistics::create([
        'user_id' => $this->player1->id,
        'current_win_streak' => 4,
        'best_win_streak' => 4,
    ]);

    $this->game->update(['winner_id' => $this->player1->id]);

    $this->action->execute($this->game);

    $stats = UserStatistics::where('user_id', $this->player1->id)->first();
    expect($stats->current_win_streak)->toBe(5);
    expect($stats->best_win_streak)->toBe(5);
});

it('calculates biggest comeback', function (): void {
    Move::factory()->create([
        'game_id' => $this->game->id,
        'user_id' => $this->player2->id,
        'score' => 50,
        'created_at' => now()->subMinutes(3),
    ]);
    Move::factory()->create([
        'game_id' => $this->game->id,
        'user_id' => $this->player1->id,
        'score' => 20,
        'created_at' => now()->subMinutes(2),
    ]);
    Move::factory()->create([
        'game_id' => $this->game->id,
        'user_id' => $this->player1->id,
        'score' => 60,
        'created_at' => now()->subMinutes(1),
    ]);

    GamePlayer::where('game_id', $this->game->id)
        ->where('user_id', $this->player1->id)
        ->update(['score' => 80]);

    $this->game->update(['winner_id' => $this->player1->id]);

    $this->action->execute($this->game->fresh());

    $stats = UserStatistics::where('user_id', $this->player1->id)->first();
    expect($stats->biggest_comeback)->toBe(50);
});

it('calculates closest victory', function (): void {
    GamePlayer::where('game_id', $this->game->id)
        ->where('user_id', $this->player1->id)
        ->update(['score' => 105]);

    GamePlayer::where('game_id', $this->game->id)
        ->where('user_id', $this->player2->id)
        ->update(['score' => 100]);

    $this->game->update(['winner_id' => $this->player1->id]);

    $this->action->execute($this->game->fresh());

    $stats = UserStatistics::where('user_id', $this->player1->id)->first();
    expect($stats->closest_victory)->toBe(5);
});

it('tracks first move wins', function (): void {
    Move::factory()->create([
        'game_id' => $this->game->id,
        'user_id' => $this->player1->id,
        'score' => 20,
        'created_at' => now()->subMinutes(10),
    ]);

    $this->game->update(['winner_id' => $this->player1->id]);

    $this->action->execute($this->game->fresh());

    $stats = UserStatistics::where('user_id', $this->player1->id)->first();
    expect($stats->first_move_wins)->toBe(1);
});

it('does not track first move win when winner did not make first move', function (): void {
    Move::factory()->create([
        'game_id' => $this->game->id,
        'user_id' => $this->player2->id,
        'score' => 20,
        'created_at' => now()->subMinutes(10),
    ]);

    $this->game->update(['winner_id' => $this->player1->id]);

    $this->action->execute($this->game->fresh());

    $stats = UserStatistics::where('user_id', $this->player1->id)->first();
    expect($stats->first_move_wins)->toBe(0);
});

it('updates elo ratings', function (): void {
    $this->game->update(['winner_id' => $this->player1->id]);

    $this->action->execute($this->game);

    $this->player1->refresh();
    $this->player2->refresh();

    expect($this->player1->elo_rating)->toBeGreaterThan(1200);
    expect($this->player2->elo_rating)->toBeLessThan(1200);
});

it('creates elo history records', function (): void {
    $this->game->update(['winner_id' => $this->player1->id]);

    $this->action->execute($this->game);

    expect(EloHistory::where('user_id', $this->player1->id)->count())->toBe(1);
    expect(EloHistory::where('user_id', $this->player2->id)->count())->toBe(1);
});

it('updates highest elo ever', function (): void {
    $this->player1->update(['elo_rating' => 1350]);
    UserStatistics::create([
        'user_id' => $this->player1->id,
        'highest_elo_ever' => 1350,
    ]);

    $this->game->update(['winner_id' => $this->player1->id]);

    $this->action->execute($this->game);

    $stats = UserStatistics::where('user_id', $this->player1->id)->first();
    expect($stats->highest_elo_ever)->toBeGreaterThan(1350);
});

it('updates lowest elo ever', function (): void {
    $this->player2->update(['elo_rating' => 1100]);
    UserStatistics::create([
        'user_id' => $this->player2->id,
        'lowest_elo_ever' => 1100,
    ]);

    $this->game->update(['winner_id' => $this->player1->id]);

    $this->action->execute($this->game);

    $stats = UserStatistics::where('user_id', $this->player2->id)->first();
    expect($stats->lowest_elo_ever)->toBeLessThan(1100);
});

it('updates head to head records for winner', function (): void {
    $this->game->update(['winner_id' => $this->player1->id]);

    $this->action->execute($this->game);

    $h2h = HeadToHeadStats::where('user_id', $this->player1->id)
        ->where('opponent_id', $this->player2->id)
        ->first();

    expect($h2h->wins)->toBe(1);
    expect($h2h->losses)->toBe(0);
});

it('updates head to head records for loser', function (): void {
    $this->game->update(['winner_id' => $this->player1->id]);

    $this->action->execute($this->game);

    $h2h = HeadToHeadStats::where('user_id', $this->player2->id)
        ->where('opponent_id', $this->player1->id)
        ->first();

    expect($h2h->wins)->toBe(0);
    expect($h2h->losses)->toBe(1);
});

it('updates head to head scores', function (): void {
    GamePlayer::where('game_id', $this->game->id)
        ->where('user_id', $this->player1->id)
        ->update(['score' => 150]);

    GamePlayer::where('game_id', $this->game->id)
        ->where('user_id', $this->player2->id)
        ->update(['score' => 120]);

    $this->game->update(['winner_id' => $this->player1->id]);

    $this->action->execute($this->game->fresh());

    $h2h = HeadToHeadStats::where('user_id', $this->player1->id)
        ->where('opponent_id', $this->player2->id)
        ->first();

    expect($h2h->total_score_for)->toBe(150);
    expect($h2h->total_score_against)->toBe(120);
});

it('does not update elo on draw', function (): void {
    $this->game->update(['winner_id' => null]);

    $this->action->execute($this->game);

    $this->player1->refresh();
    $this->player2->refresh();

    expect($this->player1->elo_rating)->toBe(1200);
    expect($this->player2->elo_rating)->toBe(1200);
});

it('updates head to head draws', function (): void {
    $this->game->update(['winner_id' => null]);

    $this->action->execute($this->game);

    $h2h1 = HeadToHeadStats::where('user_id', $this->player1->id)
        ->where('opponent_id', $this->player2->id)
        ->first();

    $h2h2 = HeadToHeadStats::where('user_id', $this->player2->id)
        ->where('opponent_id', $this->player1->id)
        ->first();

    expect($h2h1->draws)->toBe(1);
    expect($h2h2->draws)->toBe(1);
});

it('does not update closest victory when winner has lower score (resignation)', function (): void {
    // Simulate resignation scenario: player2 wins but has lower score
    GamePlayer::where('game_id', $this->game->id)
        ->where('user_id', $this->player1->id)
        ->update(['score' => 50]); // Higher score but will resign

    GamePlayer::where('game_id', $this->game->id)
        ->where('user_id', $this->player2->id)
        ->update(['score' => 20]); // Lower score but wins by resignation

    $this->game->update(['winner_id' => $this->player2->id]);

    $this->action->execute($this->game->fresh());

    $stats = UserStatistics::where('user_id', $this->player2->id)->first();
    expect($stats->closest_victory)->toBeNull();
});
