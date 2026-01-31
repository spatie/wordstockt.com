<?php

use App\Domain\Game\Models\Game;
use App\Domain\Game\Support\Rules\Turn\NoGapsRule;

beforeEach(function (): void {
    $this->rule = new NoGapsRule;
    $this->game = Mockery::mock(Game::class);
});

it('has correct identifier', function (): void {
    expect($this->rule->getIdentifier())->toBe('turn.no_gaps');
});

it('passes for single tile', function (): void {
    $board = createEmptyBoard();
    $move = createMove([
        ['letter' => 'A', 'x' => 7, 'y' => 7, 'points' => 1],
    ]);

    $result = $this->rule->validate($this->game, $move, $board);

    expect($result->passed)->toBeTrue();
});

it('passes for consecutive horizontal tiles', function (): void {
    $board = createEmptyBoard();
    $move = createMove([
        ['letter' => 'A', 'x' => 5, 'y' => 7, 'points' => 1],
        ['letter' => 'B', 'x' => 6, 'y' => 7, 'points' => 3],
        ['letter' => 'C', 'x' => 7, 'y' => 7, 'points' => 3],
    ]);

    $result = $this->rule->validate($this->game, $move, $board);

    expect($result->passed)->toBeTrue();
});

it('passes for consecutive vertical tiles', function (): void {
    $board = createEmptyBoard();
    $move = createMove([
        ['letter' => 'A', 'x' => 7, 'y' => 5, 'points' => 1],
        ['letter' => 'B', 'x' => 7, 'y' => 6, 'points' => 3],
        ['letter' => 'C', 'x' => 7, 'y' => 7, 'points' => 3],
    ]);

    $result = $this->rule->validate($this->game, $move, $board);

    expect($result->passed)->toBeTrue();
});

it('passes when gap is filled by existing tile', function (): void {
    $board = createBoardWithTiles([
        ['letter' => 'X', 'x' => 6, 'y' => 7, 'points' => 8],
    ]);
    $move = createMove([
        ['letter' => 'A', 'x' => 5, 'y' => 7, 'points' => 1],
        ['letter' => 'C', 'x' => 7, 'y' => 7, 'points' => 3],
    ]);

    $result = $this->rule->validate($this->game, $move, $board);

    expect($result->passed)->toBeTrue();
});

it('fails when there is a horizontal gap between tiles', function (): void {
    $board = createEmptyBoard();
    $move = createMove([
        ['letter' => 'A', 'x' => 5, 'y' => 7, 'points' => 1],
        ['letter' => 'C', 'x' => 7, 'y' => 7, 'points' => 3],
    ]);

    $result = $this->rule->validate($this->game, $move, $board);

    expect($result->passed)->toBeFalse()
        ->and($result->message)->toBe('There are gaps between the placed tiles.');
});

it('fails for vertical placement with gap', function (): void {
    $board = createEmptyBoard();
    $move = createMove([
        ['letter' => 'A', 'x' => 7, 'y' => 5, 'points' => 1],
        ['letter' => 'C', 'x' => 7, 'y' => 7, 'points' => 3],
    ]);

    $result = $this->rule->validate($this->game, $move, $board);

    expect($result->passed)->toBeFalse();
});
