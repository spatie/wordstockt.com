<?php

use App\Domain\Game\Models\Game;
use App\Domain\Game\Support\Rules\Turn\LinePlacementRule;

beforeEach(function (): void {
    $this->rule = new LinePlacementRule;
    $this->game = Mockery::mock(Game::class);
    $this->board = createEmptyBoard();
});

it('has correct identifier', function (): void {
    expect($this->rule->getIdentifier())->toBe('turn.line_placement');
});

it('passes for single tile placement', function (): void {
    $move = createMove([
        ['letter' => 'A', 'x' => 7, 'y' => 7, 'points' => 1],
    ]);

    $result = $this->rule->validate($this->game, $move, $this->board);

    expect($result->passed)->toBeTrue();
});

it('passes for horizontal placement', function (): void {
    $move = createMove([
        ['letter' => 'A', 'x' => 5, 'y' => 7, 'points' => 1],
        ['letter' => 'B', 'x' => 6, 'y' => 7, 'points' => 3],
        ['letter' => 'C', 'x' => 7, 'y' => 7, 'points' => 3],
    ]);

    $result = $this->rule->validate($this->game, $move, $this->board);

    expect($result->passed)->toBeTrue();
});

it('passes for vertical placement', function (): void {
    $move = createMove([
        ['letter' => 'A', 'x' => 7, 'y' => 5, 'points' => 1],
        ['letter' => 'B', 'x' => 7, 'y' => 6, 'points' => 3],
        ['letter' => 'C', 'x' => 7, 'y' => 7, 'points' => 3],
    ]);

    $result = $this->rule->validate($this->game, $move, $this->board);

    expect($result->passed)->toBeTrue();
});

it('fails for diagonal placement', function (): void {
    $move = createMove([
        ['letter' => 'A', 'x' => 5, 'y' => 5, 'points' => 1],
        ['letter' => 'B', 'x' => 6, 'y' => 6, 'points' => 3],
        ['letter' => 'C', 'x' => 7, 'y' => 7, 'points' => 3],
    ]);

    $result = $this->rule->validate($this->game, $move, $this->board);

    expect($result->passed)->toBeFalse()
        ->and($result->message)->toBe('Tiles must be placed in a single row or column.');
});

it('fails for L-shaped placement', function (): void {
    $move = createMove([
        ['letter' => 'A', 'x' => 7, 'y' => 5, 'points' => 1],
        ['letter' => 'B', 'x' => 7, 'y' => 6, 'points' => 3],
        ['letter' => 'C', 'x' => 8, 'y' => 6, 'points' => 3],
    ]);

    $result = $this->rule->validate($this->game, $move, $this->board);

    expect($result->passed)->toBeFalse();
});
