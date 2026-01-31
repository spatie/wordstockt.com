<?php

use App\Domain\Game\Enums\MoveType;
use App\Domain\Game\Models\Move;
use App\Domain\Game\Support\Rules\EndGame\ConsecutivePassRule;

beforeEach(function (): void {
    $this->rule = new ConsecutivePassRule;
});

it('has correct identifier', function (): void {
    expect($this->rule->getIdentifier())->toBe('endgame.consecutive_pass');
});

it('has correct name', function (): void {
    expect($this->rule->getName())->toBe('Consecutive Pass');
});

it('is enabled by default', function (): void {
    expect($this->rule->isEnabled())->toBeTrue();
});

it('returns correct end reason', function (): void {
    expect($this->rule->getEndReason())
        ->toBe('Four consecutive passes occurred.');
});

it('returns false with no moves', function (): void {
    $game = createGameWithPlayers();

    expect($this->rule->shouldEndGame($game))->toBeFalse();
});

it('returns false with less than 4 moves', function (): void {
    $game = createGameWithPlayers();

    Move::factory()->count(3)->create([
        'game_id' => $game->id,
        'user_id' => $game->players->first()->id,
        'type' => MoveType::Pass,
    ]);

    expect($this->rule->shouldEndGame($game))->toBeFalse();
});

it('returns false when recent moves include non-pass', function (): void {
    $game = createGameWithPlayers();

    // Create 3 passes then 1 play
    Move::factory()->count(3)->create([
        'game_id' => $game->id,
        'user_id' => $game->players->first()->id,
        'type' => MoveType::Pass,
    ]);

    Move::factory()->create([
        'game_id' => $game->id,
        'user_id' => $game->players->first()->id,
        'type' => MoveType::Play,
    ]);

    expect($this->rule->shouldEndGame($game))->toBeFalse();
});

it('returns true after 4 consecutive passes', function (): void {
    $game = createGameWithPlayers();

    Move::factory()->count(4)->create([
        'game_id' => $game->id,
        'user_id' => $game->players->first()->id,
        'type' => MoveType::Pass,
    ]);

    expect($this->rule->shouldEndGame($game))->toBeTrue();
});

it('returns true after more than 4 consecutive passes', function (): void {
    $game = createGameWithPlayers();

    Move::factory()->count(6)->create([
        'game_id' => $game->id,
        'user_id' => $game->players->first()->id,
        'type' => MoveType::Pass,
    ]);

    expect($this->rule->shouldEndGame($game))->toBeTrue();
});

it('returns false when 4th most recent move is not a pass', function (): void {
    $game = createGameWithPlayers();

    // Create 1 play, then 3 passes
    Move::factory()->create([
        'game_id' => $game->id,
        'user_id' => $game->players->first()->id,
        'type' => MoveType::Play,
        'created_at' => now()->subMinutes(10),
    ]);

    Move::factory()->count(3)->create([
        'game_id' => $game->id,
        'user_id' => $game->players->first()->id,
        'type' => MoveType::Pass,
    ]);

    expect($this->rule->shouldEndGame($game))->toBeFalse();
});
