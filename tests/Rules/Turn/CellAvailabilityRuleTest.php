<?php

use App\Domain\Game\Models\Game;
use App\Domain\Game\Support\Rules\Turn\CellAvailabilityRule;

beforeEach(function (): void {
    $this->rule = new CellAvailabilityRule;
    $this->game = Mockery::mock(Game::class);
});

it('has correct identifier', function (): void {
    expect($this->rule->getIdentifier())->toBe('turn.cell_availability');
});

it('has correct name', function (): void {
    expect($this->rule->getName())->toBe('Cell Availability');
});

it('is enabled by default', function (): void {
    expect($this->rule->isEnabled())->toBeTrue();
});

it('passes when all target cells are empty', function (): void {
    $board = createEmptyBoard();
    $move = createMove([
        ['letter' => 'A', 'x' => 7, 'y' => 7, 'points' => 1],
        ['letter' => 'B', 'x' => 8, 'y' => 7, 'points' => 3],
    ]);

    $result = $this->rule->validate($this->game, $move, $board);

    expect($result->passed)->toBeTrue();
});

it('fails when a cell is already occupied', function (): void {
    $board = createBoardWithTiles([
        ['letter' => 'X', 'x' => 7, 'y' => 7, 'points' => 8],
    ]);
    $move = createMove([
        ['letter' => 'A', 'x' => 7, 'y' => 7, 'points' => 1],
    ]);

    $result = $this->rule->validate($this->game, $move, $board);

    expect($result->passed)->toBeFalse()
        ->and($result->message)->toBe('Position is already occupied by another tile.');
});

it('fails when any cell in multi-tile move is occupied', function (): void {
    $board = createBoardWithTiles([
        ['letter' => 'X', 'x' => 8, 'y' => 7, 'points' => 8],
    ]);
    $move = createMove([
        ['letter' => 'A', 'x' => 7, 'y' => 7, 'points' => 1],
        ['letter' => 'B', 'x' => 8, 'y' => 7, 'points' => 3],
    ]);

    $result = $this->rule->validate($this->game, $move, $board);

    expect($result->passed)->toBeFalse();
});
