<?php

use App\Domain\Game\Support\Scoring\Rules\LetterScoreRule;
use App\Domain\Game\Support\Scoring\ScoringResult;

beforeEach(function (): void {
    $this->rule = new LetterScoreRule;
});

it('has correct identifier', function (): void {
    expect($this->rule->getIdentifier())->toBe('scoring.letter_score');
});

it('has correct name', function (): void {
    expect($this->rule->getName())->toBe('Letter Score');
});

it('is enabled by default', function (): void {
    expect($this->rule->isEnabled())->toBeTrue();
});

it('calculates base letter scores', function (): void {
    // Use positions without multipliers - row y=1, x positions 2,3,4 have no special squares
    $tiles = [
        ['letter' => 'C', 'points' => 3, 'x' => 2, 'y' => 1, 'is_blank' => false],
        ['letter' => 'A', 'points' => 1, 'x' => 3, 'y' => 1, 'is_blank' => false],
        ['letter' => 'T', 'points' => 1, 'x' => 4, 'y' => 1, 'is_blank' => false],
    ];

    $context = createScoringContext(
        words: [createWordData('CAT', $tiles)],
        placedTiles: $tiles,
    );

    $result = $this->rule->apply($context, ScoringResult::empty());

    // CAT = 3+1+1 = 5 (no multipliers)
    expect($result->getWordsTotal())->toBe(5);
});

it('applies double letter multiplier', function (): void {
    // Position (0,3) is a Double Letter square
    $tiles = [
        ['letter' => 'A', 'points' => 1, 'x' => 0, 'y' => 3, 'is_blank' => false],
    ];

    $context = createScoringContext(
        words: [createWordData('A', $tiles)],
        placedTiles: $tiles,
    );

    $result = $this->rule->apply($context, ScoringResult::empty());

    // A = 1 * 2 (DL) = 2
    expect($result->getWordsTotal())->toBe(2);
});

it('applies triple letter multiplier', function (): void {
    // Position (1,5) is a Triple Letter square
    $tiles = [
        ['letter' => 'Q', 'points' => 10, 'x' => 1, 'y' => 5, 'is_blank' => false],
    ];

    $context = createScoringContext(
        words: [createWordData('Q', $tiles)],
        placedTiles: $tiles,
    );

    $result = $this->rule->apply($context, ScoringResult::empty());

    // Q = 10 * 3 (TL) = 30
    expect($result->getWordsTotal())->toBe(30);
});

it('applies double word multiplier', function (): void {
    // Position (7,7) is center - Double Word square
    $tiles = [
        ['letter' => 'C', 'points' => 3, 'x' => 7, 'y' => 7, 'is_blank' => false],
        ['letter' => 'A', 'points' => 1, 'x' => 8, 'y' => 7, 'is_blank' => false],
        ['letter' => 'T', 'points' => 1, 'x' => 9, 'y' => 7, 'is_blank' => false],
    ];

    $context = createScoringContext(
        words: [createWordData('CAT', $tiles)],
        placedTiles: $tiles,
    );

    $result = $this->rule->apply($context, ScoringResult::empty());

    // CAT = (3+1+1) * 2 (DW at 7,7) = 10
    expect($result->getWordsTotal())->toBe(10);
});

it('applies triple word multiplier', function (): void {
    // Position (0,0) is a Triple Word square
    $tiles = [
        ['letter' => 'C', 'points' => 3, 'x' => 0, 'y' => 0, 'is_blank' => false],
        ['letter' => 'A', 'points' => 1, 'x' => 1, 'y' => 0, 'is_blank' => false],
        ['letter' => 'T', 'points' => 1, 'x' => 2, 'y' => 0, 'is_blank' => false],
    ];

    $context = createScoringContext(
        words: [createWordData('CAT', $tiles)],
        placedTiles: $tiles,
    );

    $result = $this->rule->apply($context, ScoringResult::empty());

    // CAT = (3+1+1) * 3 (TW at 0,0) = 15
    expect($result->getWordsTotal())->toBe(15);
});

it('gives blank tiles zero points', function (): void {
    $tiles = [
        ['letter' => 'C', 'points' => 3, 'x' => 0, 'y' => 0, 'is_blank' => false],
        ['letter' => 'A', 'points' => 1, 'x' => 1, 'y' => 0, 'is_blank' => true], // blank
        ['letter' => 'T', 'points' => 1, 'x' => 2, 'y' => 0, 'is_blank' => false],
    ];

    $context = createScoringContext(
        words: [createWordData('CAT', $tiles)],
        placedTiles: $tiles,
    );

    $result = $this->rule->apply($context, ScoringResult::empty());

    // CAT = (3+0+1) * 3 (TW at 0,0) = 12
    expect($result->getWordsTotal())->toBe(12);
});

it('does not apply multipliers to previously placed tiles', function (): void {
    // Position (7,7) is center - Double Word square
    // Only tile at (8,7) is newly placed, tile at (7,7) was already there
    $allTiles = [
        ['letter' => 'C', 'points' => 3, 'x' => 7, 'y' => 7, 'is_blank' => false],
        ['letter' => 'A', 'points' => 1, 'x' => 8, 'y' => 7, 'is_blank' => false],
    ];
    $placedTiles = [
        ['letter' => 'A', 'points' => 1, 'x' => 8, 'y' => 7, 'is_blank' => false],
    ];

    $context = createScoringContext(
        words: [createWordData('CA', $allTiles)],
        placedTiles: $placedTiles,
    );

    $result = $this->rule->apply($context, ScoringResult::empty());

    // CA = 3+1 = 4 (no DW because 7,7 is not newly placed)
    expect($result->getWordsTotal())->toBe(4);
});

it('stacks multiple word multipliers', function (): void {
    // Positions (0,0) and (7,0) are both Triple Word squares on row y=0
    // Position (3,0) is a Double Letter
    $tiles = [];
    for ($x = 0; $x <= 7; $x++) {
        $tiles[] = ['letter' => 'A', 'points' => 1, 'x' => $x, 'y' => 0, 'is_blank' => false];
    }

    $context = createScoringContext(
        words: [createWordData('AAAAAAAA', $tiles)],
        placedTiles: $tiles,
    );

    $result = $this->rule->apply($context, ScoringResult::empty());

    // 8 A's: 7 regular (7 pts) + 1 DL at (3,0) (2 pts) = 9 base points
    // TW at (0,0) * TW at (7,0) = 9 * 3 * 3 = 81
    expect($result->getWordsTotal())->toBe(81);
});

it('scores multiple words independently', function (): void {
    $tiles1 = [
        ['letter' => 'C', 'points' => 3, 'x' => 0, 'y' => 0, 'is_blank' => false],
        ['letter' => 'A', 'points' => 1, 'x' => 1, 'y' => 0, 'is_blank' => false],
        ['letter' => 'T', 'points' => 1, 'x' => 2, 'y' => 0, 'is_blank' => false],
    ];
    $tiles2 = [
        ['letter' => 'D', 'points' => 2, 'x' => 3, 'y' => 3, 'is_blank' => false],
        ['letter' => 'O', 'points' => 1, 'x' => 4, 'y' => 3, 'is_blank' => false],
        ['letter' => 'G', 'points' => 2, 'x' => 5, 'y' => 3, 'is_blank' => false],
    ];

    $context = createScoringContext(
        words: [
            createWordData('CAT', $tiles1),
            createWordData('DOG', $tiles2),
        ],
        placedTiles: array_merge($tiles1, $tiles2),
    );

    $result = $this->rule->apply($context, ScoringResult::empty());

    // CAT = (3+1+1) * 3 (TW at 0,0) = 15
    // DOG at (3,3) - (3,3) is DW, so (2+1+2) * 2 = 10
    expect($result->getWordsTotal())->toBe(25);
});

it('tracks word scores in result', function (): void {
    $tiles = [
        ['letter' => 'C', 'points' => 3, 'x' => 7, 'y' => 7, 'is_blank' => false],
        ['letter' => 'A', 'points' => 1, 'x' => 8, 'y' => 7, 'is_blank' => false],
        ['letter' => 'T', 'points' => 1, 'x' => 9, 'y' => 7, 'is_blank' => false],
    ];

    $context = createScoringContext(
        words: [createWordData('CAT', $tiles)],
        placedTiles: $tiles,
    );

    $result = $this->rule->apply($context, ScoringResult::empty());
    $wordScores = $result->getWordScores();

    expect($wordScores)->toHaveCount(1)
        ->and($wordScores->first()['word'])->toBe('CAT')
        ->and($wordScores->first()['multipliedScore'])->toBe(10);
});
