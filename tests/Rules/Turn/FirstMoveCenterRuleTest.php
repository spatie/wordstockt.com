<?php

use App\Domain\Game\Models\Game;
use App\Domain\Game\Support\Rules\Turn\FirstMoveCenterRule;

beforeEach(function (): void {
    $this->rule = new FirstMoveCenterRule;
    $this->game = Mockery::mock(Game::class);
});

it('has correct identifier', function (): void {
    expect($this->rule->getIdentifier())->toBe('turn.first_move_center');
});

it('passes when first move covers center', function (): void {
    $board = createEmptyBoard();
    $move = createMove([
        ['letter' => 'A', 'x' => 7, 'y' => 7, 'points' => 1],
    ]);

    $result = $this->rule->validate($this->game, $move, $board);

    expect($result->passed)->toBeTrue();
});

it('passes when multi-tile first move covers center', function (): void {
    $board = createEmptyBoard();
    $move = createMove([
        ['letter' => 'A', 'x' => 6, 'y' => 7, 'points' => 1],
        ['letter' => 'B', 'x' => 7, 'y' => 7, 'points' => 3],
        ['letter' => 'C', 'x' => 8, 'y' => 7, 'points' => 3],
    ]);

    $result = $this->rule->validate($this->game, $move, $board);

    expect($result->passed)->toBeTrue();
});

it('fails when first move does not cover center', function (): void {
    $board = createEmptyBoard();
    $move = createMove([
        ['letter' => 'A', 'x' => 0, 'y' => 0, 'points' => 1],
    ]);

    $result = $this->rule->validate($this->game, $move, $board);

    expect($result->passed)->toBeFalse()
        ->and($result->message)->toBe('The first move must cover the center square.');
});

it('passes for non-first move not covering center', function (): void {
    $board = createBoardWithTiles([
        ['letter' => 'X', 'x' => 7, 'y' => 7, 'points' => 8],
    ]);
    $move = createMove([
        ['letter' => 'A', 'x' => 8, 'y' => 7, 'points' => 1],
    ]);

    $result = $this->rule->validate($this->game, $move, $board);

    expect($result->passed)->toBeTrue();
});
