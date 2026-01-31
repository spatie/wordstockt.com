<?php

use App\Domain\Game\Enums\SquareType;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Support\Board;
use App\Domain\Game\Support\Scoring\Rules\LetterScoreRule;
use App\Domain\Game\Support\Scoring\Rules\WordLengthBonusRule;
use App\Domain\Game\Support\Scoring\ScoringEngine;
use App\Domain\Game\Support\Scoring\ScoringResult;

describe('Center Star Square Scoring', function (): void {
    it('treats center square as 2W on standard board', function (): void {
        $board = new Board;

        $squareType = $board->getSquareType(7, 7, null);

        expect($squareType)->toBe(SquareType::DoubleWord);
    });

    it('treats STAR as no bonus in custom board template', function (): void {
        $board = new Board;
        $template = createCustomBoardTemplateWithStarCenter();

        $squareType = $board->getSquareType(7, 7, $template);

        // BUG: This returns null instead of DoubleWord
        // According to rules.md, center should be DW (Double Word)
        expect($squareType)->toBeNull();
    });

    it('should treat STAR as 2W according to game rules', function (): void {
        // According to analysis/rules.md:
        // "Center square is typically a Double Word (DW) multiplier"
        // The STAR square should act as a 2W, not as "no bonus"

        $board = new Board;
        $template = createCustomBoardTemplateWithStarCenter();

        $squareType = $board->getSquareType(7, 7, $template);

        // This test documents the expected behavior (currently fails)
        // When fixed, STAR should return DoubleWord
        expect($squareType)->toBe(SquareType::DoubleWord);
    })->skip('BUG: STAR currently returns null instead of DoubleWord');
});

describe('HELEN Scoring Investigation', function (): void {
    it('calculates HELEN score on standard board (no custom template)', function (): void {
        $engine = (new ScoringEngine)
            ->addRule(new LetterScoreRule)
            ->addRule(new WordLengthBonusRule);

        $game = Mockery::mock(Game::class);
        $game->shouldReceive('getAttribute')->with('board_template')->andReturn(null);
        $board = new Board;

        // HELEN starting at x=5, so L lands on center (7,7)
        $tiles = [
            ['letter' => 'H', 'points' => 4, 'x' => 5, 'y' => 7, 'is_blank' => false],
            ['letter' => 'E', 'points' => 1, 'x' => 6, 'y' => 7, 'is_blank' => false],
            ['letter' => 'L', 'points' => 3, 'x' => 7, 'y' => 7, 'is_blank' => false],
            ['letter' => 'E', 'points' => 1, 'x' => 8, 'y' => 7, 'is_blank' => false],
            ['letter' => 'N', 'points' => 1, 'x' => 9, 'y' => 7, 'is_blank' => false],
        ];
        $words = [createWordData('HELEN', $tiles)];

        $result = $engine->calculateMoveScore($game, $words, $tiles, $board);

        // Base: H(4) + E(1) + L(3) + E(1) + N(1) = 10
        // L at (7,7) = 2W, so 10 × 2 = 20
        // 5-tile bonus = +25
        // Total = 45
        expect($result->getWordsTotal())->toBe(20)
            ->and($result->getBonusTotal())->toBe(25)
            ->and($result->getTotal())->toBe(45);
    });

    it('calculates HELEN score with custom board template (STAR at center)', function (): void {
        $engine = (new ScoringEngine)
            ->addRule(new LetterScoreRule)
            ->addRule(new WordLengthBonusRule);

        $template = createCustomBoardTemplateWithStarCenter();
        $game = Mockery::mock(Game::class);
        $game->shouldReceive('getAttribute')->with('board_template')->andReturn($template);
        $board = new Board;

        // HELEN starting at x=5, so L lands on center (7,7) which is STAR
        $tiles = [
            ['letter' => 'H', 'points' => 4, 'x' => 5, 'y' => 7, 'is_blank' => false],
            ['letter' => 'E', 'points' => 1, 'x' => 6, 'y' => 7, 'is_blank' => false],
            ['letter' => 'L', 'points' => 3, 'x' => 7, 'y' => 7, 'is_blank' => false],
            ['letter' => 'E', 'points' => 1, 'x' => 8, 'y' => 7, 'is_blank' => false],
            ['letter' => 'N', 'points' => 1, 'x' => 9, 'y' => 7, 'is_blank' => false],
        ];
        $words = [createWordData('HELEN', $tiles)];

        $result = $engine->calculateMoveScore($game, $words, $tiles, $board);

        // Current behavior (BUG):
        // STAR = no bonus, so word score = 10 (no 2W multiplier)
        // 5-tile bonus = +25
        // Total = 35

        // But screenshot shows 36, so there's still a mystery 1-point difference
        expect($result->getWordsTotal())->toBe(10)
            ->and($result->getBonusTotal())->toBe(25)
            ->and($result->getTotal())->toBe(35);
    });

    it('should score HELEN as 45 when STAR is treated as 2W', function (): void {
        // According to rules.md, STAR should be 2W
        // If STAR = 2W: 10 × 2 = 20 word score
        // Plus 5-tile bonus = +25
        // Total should be 45

        $engine = (new ScoringEngine)
            ->addRule(new LetterScoreRule)
            ->addRule(new WordLengthBonusRule);

        $template = createCustomBoardTemplateWithStarCenter();
        $game = Mockery::mock(Game::class);
        $game->shouldReceive('getAttribute')->with('board_template')->andReturn($template);
        $board = new Board;

        $tiles = [
            ['letter' => 'H', 'points' => 4, 'x' => 5, 'y' => 7, 'is_blank' => false],
            ['letter' => 'E', 'points' => 1, 'x' => 6, 'y' => 7, 'is_blank' => false],
            ['letter' => 'L', 'points' => 3, 'x' => 7, 'y' => 7, 'is_blank' => false],
            ['letter' => 'E', 'points' => 1, 'x' => 8, 'y' => 7, 'is_blank' => false],
            ['letter' => 'N', 'points' => 1, 'x' => 9, 'y' => 7, 'is_blank' => false],
        ];
        $words = [createWordData('HELEN', $tiles)];

        $result = $engine->calculateMoveScore($game, $words, $tiles, $board);

        // Expected when STAR bug is fixed:
        expect($result->getWordsTotal())->toBe(20)
            ->and($result->getBonusTotal())->toBe(25)
            ->and($result->getTotal())->toBe(45);
    })->skip('BUG: STAR currently gives no bonus instead of 2W');
});

describe('Word Length Bonus Values', function (): void {
    it('gives correct bonus for 2 tiles', function (): void {
        $context = createScoringContextWithTileCount(2);
        $rule = new WordLengthBonusRule;

        $result = $rule->apply($context, ScoringResult::empty());

        expect($result->getBonusTotal())->toBe(3);
    });

    it('gives correct bonus for 3 tiles', function (): void {
        $context = createScoringContextWithTileCount(3);
        $rule = new WordLengthBonusRule;

        $result = $rule->apply($context, ScoringResult::empty());

        expect($result->getBonusTotal())->toBe(6);
    });

    it('gives correct bonus for 4 tiles', function (): void {
        $context = createScoringContextWithTileCount(4);
        $rule = new WordLengthBonusRule;

        $result = $rule->apply($context, ScoringResult::empty());

        expect($result->getBonusTotal())->toBe(12);
    });

    it('gives correct bonus for 5 tiles', function (): void {
        $context = createScoringContextWithTileCount(5);
        $rule = new WordLengthBonusRule;

        $result = $rule->apply($context, ScoringResult::empty());

        expect($result->getBonusTotal())->toBe(25);
    });

    it('gives correct bonus for 6 tiles', function (): void {
        $context = createScoringContextWithTileCount(6);
        $rule = new WordLengthBonusRule;

        $result = $rule->apply($context, ScoringResult::empty());

        expect($result->getBonusTotal())->toBe(50);
    });

    it('gives correct bonus for 7 tiles', function (): void {
        $context = createScoringContextWithTileCount(7);
        $rule = new WordLengthBonusRule;

        $result = $rule->apply($context, ScoringResult::empty());

        expect($result->getBonusTotal())->toBe(100);
    });
});

describe('Investigating 36 Point Score', function (): void {
    it('scores 36 when HELEN starts at x=7 with N on 2L (current behavior)', function (): void {
        // The screenshot shows 36 points for HELEN
        // This is achieved when H starts at x=7, putting N on the 2L at x=11

        $engine = (new ScoringEngine)
            ->addRule(new LetterScoreRule)
            ->addRule(new WordLengthBonusRule);

        $template = createCustomBoardTemplateWithStarCenter();
        $game = Mockery::mock(Game::class);
        $game->shouldReceive('getAttribute')->with('board_template')->andReturn($template);
        $board = new Board;

        // HELEN starting at x=7: H(7), E(8), L(9), E(10), N(11)
        // N lands on 2L at (11,7)
        $tiles = [
            ['letter' => 'H', 'points' => 4, 'x' => 7, 'y' => 7, 'is_blank' => false],
            ['letter' => 'E', 'points' => 1, 'x' => 8, 'y' => 7, 'is_blank' => false],
            ['letter' => 'L', 'points' => 3, 'x' => 9, 'y' => 7, 'is_blank' => false],
            ['letter' => 'E', 'points' => 1, 'x' => 10, 'y' => 7, 'is_blank' => false],
            ['letter' => 'N', 'points' => 1, 'x' => 11, 'y' => 7, 'is_blank' => false],
        ];
        $words = [createWordData('HELEN', $tiles)];

        $result = $engine->calculateMoveScore($game, $words, $tiles, $board);

        // Base: H(4) + E(1) + L(3) + E(1) + N(1×2 for 2L) = 11
        // STAR at (7,7) = no word multiplier (BUG: should be 2W)
        // 5-tile bonus = +25
        // Total = 36
        expect($result->getWordsTotal())->toBe(11)
            ->and($result->getBonusTotal())->toBe(25)
            ->and($result->getTotal())->toBe(36);
    });

    it('should score 47 when STAR is treated as 2W (expected behavior)', function (): void {
        // According to rules.md, the center STAR should be a 2W multiplier
        // If STAR = 2W, the score should be:
        // Base: H(4) + E(1) + L(3) + E(1) + N(1×2 for 2L) = 11
        // H at center = 2W, so 11 × 2 = 22
        // 5-tile bonus = +25
        // Total = 47

        $engine = (new ScoringEngine)
            ->addRule(new LetterScoreRule)
            ->addRule(new WordLengthBonusRule);

        $template = createCustomBoardTemplateWithStarCenter();
        $game = Mockery::mock(Game::class);
        $game->shouldReceive('getAttribute')->with('board_template')->andReturn($template);
        $board = new Board;

        $tiles = [
            ['letter' => 'H', 'points' => 4, 'x' => 7, 'y' => 7, 'is_blank' => false],
            ['letter' => 'E', 'points' => 1, 'x' => 8, 'y' => 7, 'is_blank' => false],
            ['letter' => 'L', 'points' => 3, 'x' => 9, 'y' => 7, 'is_blank' => false],
            ['letter' => 'E', 'points' => 1, 'x' => 10, 'y' => 7, 'is_blank' => false],
            ['letter' => 'N', 'points' => 1, 'x' => 11, 'y' => 7, 'is_blank' => false],
        ];
        $words = [createWordData('HELEN', $tiles)];

        $result = $engine->calculateMoveScore($game, $words, $tiles, $board);

        // When STAR bug is fixed, this should pass:
        expect($result->getWordsTotal())->toBe(22)
            ->and($result->getBonusTotal())->toBe(25)
            ->and($result->getTotal())->toBe(47);
    })->skip('BUG: STAR currently gives no 2W multiplier');

    it('checks what score would give 36 points', function (): void {
        // 36 - 25 (5-tile bonus) = 11 word score
        // For word score to be 11, we need base 10 + 1 from a 2L
        // OR base 11 with no multiplier

        // If one E (1 point) is on 2L: 4 + 2 + 3 + 1 + 1 = 11
        // This would require a 2L at position where E lands

        $board = new Board;

        // Check 2L positions on row 7
        $dlPositions = [];
        for ($x = 0; $x < 15; $x++) {
            $type = $board->getSquareType($x, 7, null);
            if ($type === SquareType::DoubleLetter) {
                $dlPositions[] = $x;
            }
        }

        // Row 7 has 2L at positions 3 and 11
        expect($dlPositions)->toBe([3, 11]);

        // For HELEN to have an E on position 3:
        // E at x=3 means start at x=2 (H at 2, E at 3, L at 4, E at 5, N at 6)
        // But that doesn't cover center (7,7), which is required for first move

        // For HELEN to have N on position 11:
        // N at x=11 means start at x=7 (H at 7, E at 8, L at 9, E at 10, N at 11)
        // H would be at center, which is 2W on standard board
    });

    it('calculates score with N on 2L at position 11', function (): void {
        $engine = (new ScoringEngine)
            ->addRule(new LetterScoreRule)
            ->addRule(new WordLengthBonusRule);

        // Using standard board (center = 2W)
        $game = Mockery::mock(Game::class);
        $game->shouldReceive('getAttribute')->with('board_template')->andReturn(null);
        $board = new Board;

        // HELEN starting at x=7, so H is at center (2W) and N lands on 2L at x=11
        $tiles = [
            ['letter' => 'H', 'points' => 4, 'x' => 7, 'y' => 7, 'is_blank' => false],
            ['letter' => 'E', 'points' => 1, 'x' => 8, 'y' => 7, 'is_blank' => false],
            ['letter' => 'L', 'points' => 3, 'x' => 9, 'y' => 7, 'is_blank' => false],
            ['letter' => 'E', 'points' => 1, 'x' => 10, 'y' => 7, 'is_blank' => false],
            ['letter' => 'N', 'points' => 1, 'x' => 11, 'y' => 7, 'is_blank' => false],
        ];
        $words = [createWordData('HELEN', $tiles)];

        $result = $engine->calculateMoveScore($game, $words, $tiles, $board);

        // Base: H(4) + E(1) + L(3) + E(1) + N(1×2 for 2L) = 4+1+3+1+2 = 11
        // H at (7,7) = 2W, so 11 × 2 = 22
        // 5-tile bonus = +25
        // Total = 47
        expect($result->getWordsTotal())->toBe(22)
            ->and($result->getBonusTotal())->toBe(25)
            ->and($result->getTotal())->toBe(47);
    });
});

/**
 * Create a custom board template with STAR at center.
 */
function createCustomBoardTemplateWithStarCenter(): array
{
    $board = new Board;
    $template = $board->getBoardTemplate();

    // The template already has STAR at center from getBoardTemplate()
    // Let's verify and ensure it's STAR
    $template[7][7] = 'STAR';

    return $template;
}

/**
 * Create a scoring context with a specific number of tiles.
 */
function createScoringContextWithTileCount(int $count): \App\Domain\Game\Support\Scoring\ScoringContext
{
    $tiles = [];
    $word = '';

    for ($i = 0; $i < $count; $i++) {
        $tiles[] = [
            'x' => $i,
            'y' => 1, // Row 1 has no multipliers at these positions
            'letter' => 'A',
            'points' => 1,
            'is_blank' => false,
        ];
        $word .= 'A';
    }

    return createScoringContext(
        words: [createWordData($word, $tiles)],
        placedTiles: $tiles,
    );
}
