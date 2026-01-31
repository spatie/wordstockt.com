<?php

use App\Domain\Game\Support\Board;

it('finds both horizontal and vertical words when placing a single tile', function (): void {
    $board = new Board;

    // Create a board with existing tiles:
    // Row 4: _ E _  (E at position x=2)
    // Row 5: _ E _  (E at position x=2, below the E at row 4)
    $boardState = $board->createEmptyBoard();
    $boardState[4][2] = ['letter' => 'E', 'points' => 1, 'is_blank' => false];
    $boardState[5][2] = ['letter' => 'E', 'points' => 1, 'is_blank' => false];

    // Place H at (1, 4) - to the left of the E
    // This should form:
    // - Horizontal word: HE (H at x=1, E at x=2)
    // - NO vertical word from H since there's nothing above/below at x=1
    $boardState[4][1] = ['letter' => 'H', 'points' => 4, 'is_blank' => false];

    $placedTiles = [
        ['x' => 1, 'y' => 4, 'letter' => 'H', 'points' => 4, 'is_blank' => false],
    ];

    $words = $board->findFormedWords($boardState, $placedTiles);

    expect($words)->toHaveCount(1);
    expect($words[0]['word'])->toBe('HE');
});

it('finds vertical word when placing single tile with tile below', function (): void {
    $board = new Board;

    // Create a board with existing tiles:
    // Row 4: _ _ E  (E at position x=2)
    // Row 5: _ E E  (E at x=1 and x=2)
    $boardState = $board->createEmptyBoard();
    $boardState[4][2] = ['letter' => 'E', 'points' => 1, 'is_blank' => false];
    $boardState[5][1] = ['letter' => 'E', 'points' => 1, 'is_blank' => false];
    $boardState[5][2] = ['letter' => 'E', 'points' => 1, 'is_blank' => false];

    // Place H at (1, 4)
    // This should form:
    // - Horizontal word: HE (H at x=1, E at x=2, row 4)
    // - Vertical word: HE (H at y=4, E at y=5, column 1)
    $boardState[4][1] = ['letter' => 'H', 'points' => 4, 'is_blank' => false];

    $placedTiles = [
        ['x' => 1, 'y' => 4, 'letter' => 'H', 'points' => 4, 'is_blank' => false],
    ];

    $words = $board->findFormedWords($boardState, $placedTiles);

    // Should find both horizontal HE and vertical HE
    expect($words)->toHaveCount(2);

    $wordStrings = collect($words)->pluck('word')->sort()->values()->all();
    expect($wordStrings)->toBe(['HE', 'HE']);

    // Verify one is horizontal and one is vertical
    $horizontalWords = collect($words)->where('horizontal', true);
    $verticalWords = collect($words)->where('horizontal', false);

    expect($horizontalWords)->toHaveCount(1);
    expect($verticalWords)->toHaveCount(1);
});
