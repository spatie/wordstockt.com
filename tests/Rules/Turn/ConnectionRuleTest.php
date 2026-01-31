<?php

use App\Domain\Game\Models\Game;
use App\Domain\Game\Support\Rules\Turn\ConnectionRule;

beforeEach(function (): void {
    $this->rule = new ConnectionRule;
    $this->game = Mockery::mock(Game::class);
});

it('has correct identifier', function (): void {
    expect($this->rule->getIdentifier())->toBe('turn.connection');
});

it('passes for first move on empty board', function (): void {
    $board = createEmptyBoard();
    $move = createMove([
        ['letter' => 'A', 'x' => 7, 'y' => 7, 'points' => 1],
    ]);

    $result = $this->rule->validate($this->game, $move, $board);

    expect($result->passed)->toBeTrue();
});

it('passes when new tile connects horizontally to the left', function (): void {
    $board = createBoardWithTiles([
        ['letter' => 'X', 'x' => 7, 'y' => 7, 'points' => 8],
    ]);
    $move = createMove([
        ['letter' => 'A', 'x' => 8, 'y' => 7, 'points' => 1],
    ]);

    $result = $this->rule->validate($this->game, $move, $board);

    expect($result->passed)->toBeTrue();
});

it('passes when new tile connects horizontally to the right', function (): void {
    $board = createBoardWithTiles([
        ['letter' => 'X', 'x' => 7, 'y' => 7, 'points' => 8],
    ]);
    $move = createMove([
        ['letter' => 'A', 'x' => 6, 'y' => 7, 'points' => 1],
    ]);

    $result = $this->rule->validate($this->game, $move, $board);

    expect($result->passed)->toBeTrue();
});

it('passes when new tile connects vertically below', function (): void {
    $board = createBoardWithTiles([
        ['letter' => 'X', 'x' => 7, 'y' => 7, 'points' => 8],
    ]);
    $move = createMove([
        ['letter' => 'A', 'x' => 7, 'y' => 8, 'points' => 1],
    ]);

    $result = $this->rule->validate($this->game, $move, $board);

    expect($result->passed)->toBeTrue();
});

it('passes when new tile connects vertically above', function (): void {
    $board = createBoardWithTiles([
        ['letter' => 'X', 'x' => 7, 'y' => 7, 'points' => 8],
    ]);
    $move = createMove([
        ['letter' => 'A', 'x' => 7, 'y' => 6, 'points' => 1],
    ]);

    $result = $this->rule->validate($this->game, $move, $board);

    expect($result->passed)->toBeTrue();
});

it('fails when tiles do not connect to existing', function (): void {
    $board = createBoardWithTiles([
        ['letter' => 'X', 'x' => 7, 'y' => 7, 'points' => 8],
    ]);
    $move = createMove([
        ['letter' => 'A', 'x' => 0, 'y' => 0, 'points' => 1],
    ]);

    $result = $this->rule->validate($this->game, $move, $board);

    expect($result->passed)->toBeFalse()
        ->and($result->message)->toBe('Tiles must connect to existing tiles on the board.');
});

it('fails when tiles are diagonal to existing', function (): void {
    $board = createBoardWithTiles([
        ['letter' => 'X', 'x' => 7, 'y' => 7, 'points' => 8],
    ]);
    $move = createMove([
        ['letter' => 'A', 'x' => 8, 'y' => 8, 'points' => 1],
    ]);

    $result = $this->rule->validate($this->game, $move, $board);

    expect($result->passed)->toBeFalse();
});
