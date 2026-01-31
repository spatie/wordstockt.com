<?php

use App\Domain\Game\Actions\Stats\UpdateMoveStatsAction;
use App\Domain\Game\Models\HeadToHeadStats;
use App\Domain\Game\Models\Move;
use App\Domain\Game\Support\Scoring\ScoringResult;
use App\Domain\User\Models\User;
use App\Domain\User\Models\UserStatistics;

beforeEach(function (): void {
    $this->action = app(UpdateMoveStatsAction::class);
    $this->user = User::factory()->create();
    $this->game = createGameWithPlayers(player1: $this->user);
});

it('creates user statistics if not exists', function (): void {
    $move = Move::factory()->create([
        'game_id' => $this->game->id,
        'user_id' => $this->user->id,
        'score' => 10,
        'tiles' => [],
    ]);

    $this->action->execute($this->user, $move, $this->game, ScoringResult::empty());

    expect(UserStatistics::where('user_id', $this->user->id)->exists())->toBeTrue();
});

it('updates total points scored', function (): void {
    $move = Move::factory()->create([
        'game_id' => $this->game->id,
        'user_id' => $this->user->id,
        'score' => 25,
        'tiles' => [],
    ]);

    $this->action->execute($this->user, $move, $this->game, ScoringResult::empty());

    $stats = UserStatistics::where('user_id', $this->user->id)->first();
    expect($stats->total_points_scored)->toBe(25);
});

it('updates total words played', function (): void {
    $move = Move::factory()->create([
        'game_id' => $this->game->id,
        'user_id' => $this->user->id,
        'score' => 20,
        'tiles' => [],
    ]);

    $scoringResult = ScoringResult::empty()
        ->addWordScore('TEST', 10, 20)
        ->addWordScore('EXTRA', 8, 16);

    $this->action->execute($this->user, $move, $this->game, $scoringResult);

    $stats = UserStatistics::where('user_id', $this->user->id)->first();
    expect($stats->total_words_played)->toBe(2);
});

it('tracks highest scoring word', function (): void {
    $move = Move::factory()->create([
        'game_id' => $this->game->id,
        'user_id' => $this->user->id,
        'score' => 50,
        'tiles' => [],
    ]);

    $scoringResult = ScoringResult::empty()
        ->addWordScore('small', 5, 10)
        ->addWordScore('BIGGEST', 25, 50);

    $this->action->execute($this->user, $move, $this->game, $scoringResult);

    $stats = UserStatistics::where('user_id', $this->user->id)->first();
    expect($stats->highest_scoring_word)->toBe('BIGGEST');
    expect($stats->highest_scoring_word_score)->toBe(50);
});

it('only updates highest word if score is higher', function (): void {
    UserStatistics::create([
        'user_id' => $this->user->id,
        'highest_scoring_word' => 'PREVIOUS',
        'highest_scoring_word_score' => 100,
    ]);

    $move = Move::factory()->create([
        'game_id' => $this->game->id,
        'user_id' => $this->user->id,
        'score' => 20,
        'tiles' => [],
    ]);

    $scoringResult = ScoringResult::empty()->addWordScore('smaller', 10, 20);

    $this->action->execute($this->user, $move, $this->game, $scoringResult);

    $stats = UserStatistics::where('user_id', $this->user->id)->first();
    expect($stats->highest_scoring_word)->toBe('PREVIOUS');
    expect($stats->highest_scoring_word_score)->toBe(100);
});

it('tracks highest scoring move', function (): void {
    $move = Move::factory()->create([
        'game_id' => $this->game->id,
        'user_id' => $this->user->id,
        'score' => 75,
        'tiles' => [],
    ]);

    $this->action->execute($this->user, $move, $this->game, ScoringResult::empty());

    $stats = UserStatistics::where('user_id', $this->user->id)->first();
    expect($stats->highest_scoring_move)->toBe(75);
});

it('counts bingos', function (): void {
    $move = Move::factory()->create([
        'game_id' => $this->game->id,
        'user_id' => $this->user->id,
        'score' => 100,
        'tiles' => [],
    ]);

    $scoringResult = ScoringResult::empty()->addBonus('bingo_bonus', 50, 'Bingo!');

    $this->action->execute($this->user, $move, $this->game, $scoringResult);

    $stats = UserStatistics::where('user_id', $this->user->id)->first();
    expect($stats->bingos_count)->toBe(1);
});

it('counts blank tiles played', function (): void {
    $move = Move::factory()->create([
        'game_id' => $this->game->id,
        'user_id' => $this->user->id,
        'score' => 20,
        'tiles' => [
            ['letter' => 'A', 'x' => 7, 'y' => 7, 'points' => 0, 'is_blank' => true],
            ['letter' => 'B', 'x' => 8, 'y' => 7, 'points' => 3, 'is_blank' => false],
            ['letter' => 'C', 'x' => 9, 'y' => 7, 'points' => 0, 'is_blank' => true],
        ],
    ]);

    $this->action->execute($this->user, $move, $this->game, ScoringResult::empty());

    $stats = UserStatistics::where('user_id', $this->user->id)->first();
    expect($stats->blank_tiles_played)->toBe(2);
});

it('counts triple word tiles used', function (): void {
    $move = Move::factory()->create([
        'game_id' => $this->game->id,
        'user_id' => $this->user->id,
        'score' => 30,
        'tiles' => [
            ['letter' => 'A', 'x' => 0, 'y' => 0, 'points' => 1, 'is_blank' => false],
        ],
    ]);

    $this->action->execute($this->user, $move, $this->game, ScoringResult::empty());

    $stats = UserStatistics::where('user_id', $this->user->id)->first();
    expect($stats->triple_word_tiles_used)->toBe(1);
});

it('counts double word tiles used', function (): void {
    $move = Move::factory()->create([
        'game_id' => $this->game->id,
        'user_id' => $this->user->id,
        'score' => 20,
        'tiles' => [
            ['letter' => 'A', 'x' => 7, 'y' => 7, 'points' => 1, 'is_blank' => false],
        ],
    ]);

    $this->action->execute($this->user, $move, $this->game, ScoringResult::empty());

    $stats = UserStatistics::where('user_id', $this->user->id)->first();
    expect($stats->double_word_tiles_used)->toBe(1);
});

it('tracks first moves played when user makes game first move', function (): void {
    $move = Move::factory()->create([
        'game_id' => $this->game->id,
        'user_id' => $this->user->id,
        'score' => 10,
        'tiles' => [],
    ]);

    $this->action->execute($this->user, $move, $this->game->fresh(), ScoringResult::empty());

    $stats = UserStatistics::where('user_id', $this->user->id)->first();
    expect($stats->first_moves_played)->toBe(1);
});

it('does not track first move if not the game first move', function (): void {
    $opponent = $this->game->players->where('id', '!=', $this->user->id)->first();

    Move::factory()->create([
        'game_id' => $this->game->id,
        'user_id' => $opponent->id,
        'score' => 10,
        'tiles' => [],
    ]);

    $move = Move::factory()->create([
        'game_id' => $this->game->id,
        'user_id' => $this->user->id,
        'score' => 10,
        'tiles' => [],
    ]);

    $this->action->execute($this->user, $move, $this->game->fresh(), ScoringResult::empty());

    $stats = UserStatistics::where('user_id', $this->user->id)->first();
    expect($stats->first_moves_played)->toBe(0);
});

it('updates head to head best word', function (): void {
    $opponent = $this->game->players->where('id', '!=', $this->user->id)->first();

    $move = Move::factory()->create([
        'game_id' => $this->game->id,
        'user_id' => $this->user->id,
        'score' => 50,
        'tiles' => [],
    ]);

    $scoringResult = ScoringResult::empty()->addWordScore('AMAZING', 25, 50);

    $this->action->execute($this->user, $move, $this->game, $scoringResult);

    $h2h = HeadToHeadStats::where('user_id', $this->user->id)
        ->where('opponent_id', $opponent->id)
        ->first();

    expect($h2h)->not->toBeNull();
    expect($h2h->best_word)->toBe('AMAZING');
    expect($h2h->best_word_score)->toBe(50);
});

it('accumulates stats across multiple moves', function (): void {
    for ($i = 0; $i < 3; $i++) {
        $move = Move::factory()->create([
            'game_id' => $this->game->id,
            'user_id' => $this->user->id,
            'score' => 10,
            'tiles' => [],
        ]);

        $scoringResult = ScoringResult::empty()->addWordScore("WORD{$i}", 5, 10);
        $this->action->execute($this->user, $move, $this->game->fresh(), $scoringResult);
    }

    $stats = UserStatistics::where('user_id', $this->user->id)->first();
    expect($stats->total_points_scored)->toBe(30);
    expect($stats->total_words_played)->toBe(3);
});
