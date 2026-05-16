<?php

use App\Domain\Achievement\Achievements\WordMastery\BingoAchievement;
use App\Domain\Game\Models\Move;
use App\Domain\Game\Support\Scoring\ScoringResult;
use App\Domain\User\Models\User;

beforeEach(function (): void {
    $this->achievement = new BingoAchievement;
    $this->user = User::factory()->create();
    $this->game = createGameWithPlayers(player1: $this->user);
});

it('triggers when all 7 tiles are played', function (): void {
    $tiles = [];
    for ($i = 0; $i < 7; $i++) {
        $tiles[] = ['letter' => 'A', 'points' => 1, 'x' => $i, 'y' => 7, 'is_blank' => false];
    }

    $move = Move::factory()->create([
        'game_id' => $this->game->id,
        'user_id' => $this->user->id,
        'score' => 100,
        'tiles' => $tiles,
    ]);

    $context = $this->achievement->checkMove($this->user, $move, $this->game, ScoringResult::empty());

    expect($context)->not->toBeNull()
        ->and($context->data['score'])->toBe(100);
});

it('does not trigger for fewer than 7 tiles', function (): void {
    $tiles = [];
    for ($i = 0; $i < 6; $i++) {
        $tiles[] = ['letter' => 'A', 'points' => 1, 'x' => $i, 'y' => 7, 'is_blank' => false];
    }

    $move = Move::factory()->create([
        'game_id' => $this->game->id,
        'user_id' => $this->user->id,
        'score' => 30,
        'tiles' => $tiles,
    ]);

    $context = $this->achievement->checkMove($this->user, $move, $this->game, ScoringResult::empty());

    expect($context)->toBeNull();
});
