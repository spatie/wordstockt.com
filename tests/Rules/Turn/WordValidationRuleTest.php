<?php

use App\Domain\Game\Support\Board;
use App\Domain\Game\Support\Rules\Turn\WordValidationRule;
use App\Domain\Support\Models\Dictionary;

beforeEach(function (): void {
    $this->boardService = new Board;
    $this->rule = new WordValidationRule($this->boardService);
});

it('has correct identifier', function (): void {
    expect($this->rule->getIdentifier())->toBe('turn.word_validation');
});

it('has correct name', function (): void {
    expect($this->rule->getName())->toBe('Word Validation');
});

it('passes when all formed words are valid', function (): void {
    Dictionary::create(['word' => 'CAT', 'language' => 'en']);

    $game = createGameWithPlayers();
    $board = createEmptyBoard();
    $move = createMove([
        ['letter' => 'C', 'x' => 6, 'y' => 7, 'points' => 3],
        ['letter' => 'A', 'x' => 7, 'y' => 7, 'points' => 1],
        ['letter' => 'T', 'x' => 8, 'y' => 7, 'points' => 1],
    ]);

    $result = $this->rule->validate($game, $move, $board);

    expect($result->passed)->toBeTrue();
});

it('fails when word is invalid', function (): void {
    $game = createGameWithPlayers();
    $board = createEmptyBoard();
    $move = createMove([
        ['letter' => 'X', 'x' => 6, 'y' => 7, 'points' => 8],
        ['letter' => 'X', 'x' => 7, 'y' => 7, 'points' => 8],
        ['letter' => 'X', 'x' => 8, 'y' => 7, 'points' => 8],
    ]);

    $result = $this->rule->validate($game, $move, $board);

    expect($result->passed)->toBeFalse()
        ->and($result->message)->toContain('Invalid word(s): XXX');
});

it('fails when no words are formed', function (): void {
    $game = createGameWithPlayers();
    $board = createEmptyBoard();
    $move = createMove([
        ['letter' => 'A', 'x' => 7, 'y' => 7, 'points' => 1],
    ]);

    $result = $this->rule->validate($game, $move, $board);

    expect($result->passed)->toBeFalse()
        ->and($result->message)->toBe('No valid words formed.');
});

it('validates multiple words when perpendicular words are formed', function (): void {
    Dictionary::create(['word' => 'CAT', 'language' => 'en']);
    Dictionary::create(['word' => 'AAT', 'language' => 'en']);

    $game = createGameWithPlayers();
    $board = createBoardWithTiles([
        ['letter' => 'A', 'x' => 7, 'y' => 6, 'points' => 1],
        ['letter' => 'T', 'x' => 7, 'y' => 8, 'points' => 1],
    ]);
    $move = createMove([
        ['letter' => 'C', 'x' => 6, 'y' => 7, 'points' => 3],
        ['letter' => 'A', 'x' => 7, 'y' => 7, 'points' => 1],
        ['letter' => 'T', 'x' => 8, 'y' => 7, 'points' => 1],
    ]);

    $result = $this->rule->validate($game, $move, $board);

    expect($result->passed)->toBeTrue();
});
