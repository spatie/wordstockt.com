<?php

use App\Domain\Game\Models\Game;
use App\Domain\Game\Support\Rules\Turn\BoardBoundsRule;

beforeEach(function (): void {
    $this->rule = new BoardBoundsRule;
    $this->game = Mockery::mock(Game::class);
    $this->board = createEmptyBoard();
});

it('has correct identifier', function (): void {
    expect($this->rule->getIdentifier())->toBe('turn.board_bounds');
});

it('has correct name', function (): void {
    expect($this->rule->getName())->toBe('Board Bounds');
});

it('is enabled by default', function (): void {
    expect($this->rule->isEnabled())->toBeTrue();
});

it('passes for tiles within bounds', function (): void {
    $move = createMove([
        ['letter' => 'A', 'x' => 7, 'y' => 7, 'points' => 1],
        ['letter' => 'B', 'x' => 8, 'y' => 7, 'points' => 3],
    ]);

    $result = $this->rule->validate($this->game, $move, $this->board);

    expect($result->passed)->toBeTrue();
});

it('passes for tiles at board edges', function (): void {
    $move = createMove([
        ['letter' => 'A', 'x' => 0, 'y' => 0, 'points' => 1],
        ['letter' => 'B', 'x' => 14, 'y' => 14, 'points' => 3],
    ]);

    $result = $this->rule->validate($this->game, $move, $this->board);

    expect($result->passed)->toBeTrue();
});

it('fails for tile with negative x coordinate', function (): void {
    $move = createMove([
        ['letter' => 'A', 'x' => -1, 'y' => 7, 'points' => 1],
    ]);

    $result = $this->rule->validate($this->game, $move, $this->board);

    expect($result->passed)->toBeFalse()
        ->and($result->message)->toBe('Tile position is out of bounds.');
});

it('fails for tile with x coordinate >= 15', function (): void {
    $move = createMove([
        ['letter' => 'A', 'x' => 15, 'y' => 7, 'points' => 1],
    ]);

    $result = $this->rule->validate($this->game, $move, $this->board);

    expect($result->passed)->toBeFalse();
});

it('fails for tile with negative y coordinate', function (): void {
    $move = createMove([
        ['letter' => 'A', 'x' => 7, 'y' => -1, 'points' => 1],
    ]);

    $result = $this->rule->validate($this->game, $move, $this->board);

    expect($result->passed)->toBeFalse();
});

it('fails for tile with y coordinate >= 15', function (): void {
    $move = createMove([
        ['letter' => 'A', 'x' => 7, 'y' => 15, 'points' => 1],
    ]);

    $result = $this->rule->validate($this->game, $move, $this->board);

    expect($result->passed)->toBeFalse();
});
