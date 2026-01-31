<?php

use App\Domain\Game\Support\Scoring\Rules\WordLengthBonusRule;
use App\Domain\Game\Support\Scoring\ScoringResult;

beforeEach(function (): void {
    $this->rule = new WordLengthBonusRule;
});

it('has correct identifier', function (): void {
    expect($this->rule->getIdentifier())->toBe('scoring.word_length_bonus');
});

it('has correct name', function (): void {
    expect($this->rule->getName())->toBe('Word Length Bonus');
});

it('is enabled by default', function (): void {
    expect($this->rule->isEnabled())->toBeTrue();
});

// ============================================
// Tiles Played Bonus Tests
// ============================================

it('adds no bonus for playing 1 tile', function (): void {
    $context = createScoringContextWithWord('A');

    $result = $this->rule->apply($context, ScoringResult::empty());

    expect($result->getBonusTotal())->toBe(0);
});

it('adds +3 for playing 2 tiles', function (): void {
    $context = createScoringContextWithWord('IT');

    $result = $this->rule->apply($context, ScoringResult::empty());

    expect($result->getBonusTotal())->toBe(3)
        ->and($result->hasBonus('scoring.word_length_bonus'))->toBeTrue();
});

it('adds +6 for playing 3 tiles', function (): void {
    $context = createScoringContextWithWord('CAT');

    $result = $this->rule->apply($context, ScoringResult::empty());

    expect($result->getBonusTotal())->toBe(6);
});

it('adds +12 for playing 4 tiles', function (): void {
    $context = createScoringContextWithWord('BALL');

    $result = $this->rule->apply($context, ScoringResult::empty());

    expect($result->getBonusTotal())->toBe(12);
});

it('adds +25 for playing 5 tiles', function (): void {
    $context = createScoringContextWithWord('HELLO');

    $result = $this->rule->apply($context, ScoringResult::empty());

    expect($result->getBonusTotal())->toBe(25);
});

it('adds +50 for playing 6 tiles', function (): void {
    $context = createScoringContextWithWord('PLAYER');

    $result = $this->rule->apply($context, ScoringResult::empty());

    expect($result->getBonusTotal())->toBe(50);
});

it('adds +100 for playing 7 tiles (bingo)', function (): void {
    $context = createScoringContextWithWord('TESTING');

    $result = $this->rule->apply($context, ScoringResult::empty());

    expect($result->getBonusTotal())->toBe(100);
});

it('includes tile count in bonus description', function (): void {
    $context = createScoringContextWithWord('BALL');

    $result = $this->rule->apply($context, ScoringResult::empty());
    $bonuses = $result->getBonuses();

    expect($bonuses)->toHaveCount(1)
        ->and($bonuses->first()['description'])->toContain('4 tiles');
});

// ============================================
// Word Extension Bonus Tests
// ============================================

it('adds no extension bonus when extending by only 1 letter', function (): void {
    // BASKET (6 letters) + S = BASKETS (1 letter extension, not enough)
    $context = createExtendedWordContext('BASKET', 'S');

    $result = $this->rule->apply($context, ScoringResult::empty());

    // Should only have tiles played bonus (1 tile = 0 bonus)
    expect($result->getBonusTotal())->toBe(0);
});

it('adds +10 for extending a 2-letter word by 2+ letters', function (): void {
    // IT (2 letters) + EM = ITEM (2 letter extension)
    $context = createExtendedWordContext('IT', 'EM');

    $result = $this->rule->apply($context, ScoringResult::empty());

    // Tiles played: 2 tiles = +3
    // Extension: 2-letter word = +10
    expect($result->getBonusTotal())->toBe(13);
});

it('adds +12 for extending a 3-letter word by 2+ letters', function (): void {
    // CAT (3 letters) + CH = CATCH (2 letter extension)
    $context = createExtendedWordContext('CAT', 'CH');

    $result = $this->rule->apply($context, ScoringResult::empty());

    // Tiles played: 2 tiles = +3
    // Extension: 3-letter word = +12
    expect($result->getBonusTotal())->toBe(15);
});

it('adds +15 for extending a 4-letter word by 2+ letters', function (): void {
    // BALL (4 letters) + ER = BALLER (2 letter extension)
    $context = createExtendedWordContext('BALL', 'ER');

    $result = $this->rule->apply($context, ScoringResult::empty());

    // Tiles played: 2 tiles = +3
    // Extension: 4-letter word = +15
    expect($result->getBonusTotal())->toBe(18);
});

it('adds +19 for extending a 5-letter word by 2+ letters', function (): void {
    // HELLO (5 letters) + ED = HELLOED (hypothetical, 2 letter extension)
    $context = createExtendedWordContext('HELLO', 'ED');

    $result = $this->rule->apply($context, ScoringResult::empty());

    // Tiles played: 2 tiles = +3
    // Extension: 5-letter word = +19
    expect($result->getBonusTotal())->toBe(22);
});

it('adds +23 for extending a 6-letter word by 2+ letters', function (): void {
    // BASKET (6 letters) + BALL = BASKETBALL (4 letter extension)
    $context = createExtendedWordContext('BASKET', 'BALL');

    $result = $this->rule->apply($context, ScoringResult::empty());

    // Tiles played: 4 tiles = +12
    // Extension: 6-letter word = +23
    expect($result->getBonusTotal())->toBe(35);
});

it('adds +28 for extending a 7-letter word by 2+ letters', function (): void {
    $context = createExtendedWordContext('TESTING', 'ED');

    $result = $this->rule->apply($context, ScoringResult::empty());

    // Tiles played: 2 tiles = +3
    // Extension: 7-letter word = +28
    expect($result->getBonusTotal())->toBe(31);
});

it('adds +35 for extending an 8-letter word by 2+ letters', function (): void {
    $context = createExtendedWordContext('SCRABBEL', 'ED');

    $result = $this->rule->apply($context, ScoringResult::empty());

    // Tiles played: 2 tiles = +3
    // Extension: 8-letter word = +35
    expect($result->getBonusTotal())->toBe(38);
});

it('adds +100 for extending a 13-letter word by 2+ letters', function (): void {
    $context = createExtendedWordContext('COMMUNICATION', 'AL');

    $result = $this->rule->apply($context, ScoringResult::empty());

    // Tiles played: 2 tiles = +3
    // Extension: 13-letter word = +100
    expect($result->getBonusTotal())->toBe(103);
});

it('caps extension bonus at +100 for 14+ letter words', function (): void {
    $context = createExtendedWordContext('COMMUNICATIONS', 'ED');

    $result = $this->rule->apply($context, ScoringResult::empty());

    // Tiles played: 2 tiles = +3
    // Extension: 14-letter word = +100 (capped)
    expect($result->getBonusTotal())->toBe(103);
});

it('works with extension at the beginning of word', function (): void {
    // Adding UN to HAPPY = UNHAPPY
    $context = createExtendedWordContext('HAPPY', 'UN', extendAtEnd: false);

    $result = $this->rule->apply($context, ScoringResult::empty());

    // Tiles played: 2 tiles = +3
    // Extension: 5-letter word = +19
    expect($result->getBonusTotal())->toBe(22);
});

// ============================================
// Example Scenarios from Requirements
// ============================================

it('handles example 1: BASKET + BALL = BASKETBALL', function (): void {
    // BASKET (6 letters) extended with BALL (4 tiles)
    // Expected: 12 (for 4 tiles) + 23 (for extending 6-letter word) = 35
    $context = createExtendedWordContext('BASKET', 'BALL');

    $result = $this->rule->apply($context, ScoringResult::empty());

    expect($result->getBonusTotal())->toBe(35);
});

it('handles example 2: BASKET + S = BASKETS (no extension bonus)', function (): void {
    // BASKET (6 letters) extended with S (1 tile)
    // Expected: 0 (1 tile = no tiles bonus) + 0 (1 letter extension = no extension bonus)
    $context = createExtendedWordContext('BASKET', 'S');

    $result = $this->rule->apply($context, ScoringResult::empty());

    expect($result->getBonusTotal())->toBe(0);
});

// ============================================
// Edge Cases
// ============================================

it('only awards one extension bonus per move even with multiple qualifying words', function (): void {
    // Create a context with two words that could qualify for extension bonus
    // The rule should pick the one with the highest bonus
    $existingWord1 = 'CAT';   // 3 letters = +12 extension bonus
    $extension1 = 'CH';
    $existingWord2 = 'BASKET'; // 6 letters = +23 extension bonus
    $extension2 = 'ED';

    // Build tiles for first word (CAT + CH = CATCH)
    $tiles1 = [];
    foreach (str_split($existingWord1) as $i => $letter) {
        $tiles1[] = ['x' => $i, 'y' => 0, 'letter' => $letter, 'points' => 1, 'is_blank' => false];
    }
    foreach (str_split($extension1) as $i => $letter) {
        $tiles1[] = ['x' => strlen($existingWord1) + $i, 'y' => 0, 'letter' => $letter, 'points' => 1, 'is_blank' => false];
    }

    // Build tiles for second word (BASKET + ED = BASKETED) on different row
    $tiles2 = [];
    foreach (str_split($existingWord2) as $i => $letter) {
        $tiles2[] = ['x' => $i, 'y' => 1, 'letter' => $letter, 'points' => 1, 'is_blank' => false];
    }
    foreach (str_split($extension2) as $i => $letter) {
        $tiles2[] = ['x' => strlen($existingWord2) + $i, 'y' => 1, 'letter' => $letter, 'points' => 1, 'is_blank' => false];
    }

    // Only the new tiles are placed
    $placedTiles = [];
    foreach (str_split($extension1) as $i => $letter) {
        $placedTiles[] = ['x' => strlen($existingWord1) + $i, 'y' => 0, 'letter' => $letter, 'points' => 1, 'is_blank' => false];
    }
    foreach (str_split($extension2) as $i => $letter) {
        $placedTiles[] = ['x' => strlen($existingWord2) + $i, 'y' => 1, 'letter' => $letter, 'points' => 1, 'is_blank' => false];
    }

    $context = createScoringContext(
        words: [
            createWordData('CATCH', $tiles1),
            createWordData('BASKETED', $tiles2),
        ],
        placedTiles: $placedTiles,
    );

    $result = $this->rule->apply($context, ScoringResult::empty());

    // Tiles played: 4 tiles (CH + ED) = +12
    // Extension bonus: highest is 6-letter word (BASKET) = +23
    // Total: 35
    expect($result->getBonusTotal())->toBe(35);

    // Should only have 2 bonuses (tiles played + one extension)
    $extensionBonuses = $result->getBonuses()->filter(
        fn ($b): bool => str_contains((string) $b['rule'], 'extension')
    );
    expect($extensionBonuses)->toHaveCount(1);
});

it('applies no bonus when extending a 1-letter word', function (): void {
    // Extending A with BC = ABC (1-letter original word, doesn't qualify)
    $context = createExtendedWordContext('A', 'BC');

    $result = $this->rule->apply($context, ScoringResult::empty());

    // Tiles played: 2 tiles = +3
    // No extension bonus (original word must be 2+ letters)
    expect($result->getBonusTotal())->toBe(3);
});
