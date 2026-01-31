# WordStockt Game Rules

This document defines all game rules and their implementation as PHP rule classes.

---

## Table of Contents

1. [Rule Categories](#rule-categories)
2. [Turn Rules](#turn-rules)
3. [Game Rules](#game-rules)
4. [Scoring Rules](#scoring-rules)
5. [End Game Rules](#end-game-rules)
6. [Board Configuration](#board-configuration)
7. [PHP Implementation](#php-implementation)

---

## Rule Categories

Rules are organized into categories based on when they apply:

| Category | When Applied | Examples |
|----------|--------------|----------|
| **Turn Rules** | During tile placement validation | Line placement, connection, gaps |
| **Game Rules** | During game actions | Swap limits, pass rules, rack size |
| **Scoring Rules** | After valid move | Multipliers, bingo bonus |
| **End Game Rules** | Game termination check | Empty rack, consecutive passes |

---

## Turn Rules

### 1. Line Placement Rule
**All tiles must be placed in a single straight line.**

- Tiles can only be placed horizontally OR vertically in a single turn
- Cannot place tiles in an L-shape or scattered pattern
- Single tile placement is valid

```
Valid:          Invalid:
A B C D         A B
                    C
                    D
```

### 2. No Gaps Rule
**No empty cells between placed tiles.**

- All tiles in the line must be contiguous
- Existing board tiles can fill gaps between new tiles

```
Valid (X = existing tile):
A X B C

Invalid:
A _ B C   (gap at position 2)
```

### 3. Board Bounds Rule
**All tiles must be within the 15x15 grid.**

- X coordinates: 0-14
- Y coordinates: 0-14
- Any tile outside bounds is invalid

### 4. Cell Availability Rule
**Tiles can only be placed on empty cells.**

- Cannot place a tile on an already occupied cell
- Must check both existing board tiles and pending tiles in same turn

### 5. First Move Center Rule
**The first move must cover the center square (7,7).**

- Only applies when the board is empty
- At least one tile must be on position (7, 7)
- Center square is typically a Double Word (DW) multiplier

### 6. Connection Rule
**All moves (except first) must connect to existing tiles.**

- At least one new tile must be adjacent to an existing tile
- Adjacent means horizontally or vertically (not diagonal)
- The new tiles must extend or intersect existing words

### 7. Word Formation Rule
**All formed words must be valid dictionary words.**

- The main word formed by the placed tiles must be valid
- All perpendicular words (cross-words) must also be valid
- Single-letter "words" adjacent to nothing don't need validation

### 8. Tiles In Rack Rule
**Player must have the tiles they're trying to place.**

- All placed tiles must exist in the player's rack
- Blank tiles can represent any letter
- Cannot use more tiles than available in rack

### 9. Minimum Word Length Rule (Optional)
**Words must be at least 2 letters long.**

- Configurable: some variants allow single-letter plays
- Default: 2 letter minimum
- Only applies to the primary word, not cross-words

---

## Game Rules

### 1. Rack Size Rule
**Each player has a maximum of 7 tiles.**

- Players draw tiles to fill rack to 7 after each turn
- If bag has fewer tiles, draw all remaining
- Rack can have 0-7 tiles

### 2. Turn Order Rule
**Players alternate turns.**

- Player who created the game goes first (or random)
- Turn passes to opponent after: play, pass, or swap
- Cannot play out of turn

### 3. Swap Rule
**Players can swap tiles instead of playing.**

- Exchange 1-7 tiles from rack with tile bag
- Can only swap when tile bag has enough tiles (≥7 typically)
- Swapping counts as the player's turn
- Swapped tiles go back to bag, then draw same amount

**Configurable limits:**
- `swap_limit`: Maximum swaps per game (default: unlimited)
- `swap_minimum_bag`: Minimum tiles in bag to allow swap (default: 7)

### 4. Pass Rule
**Players can pass their turn.**

- No tiles placed, no tiles swapped
- Turn passes to opponent
- Counts toward consecutive pass limit

**Configurable:**
- `allow_pass`: Whether passing is allowed (default: true)

### 5. Challenge Rule (Optional)
**Players can challenge opponent's words.**

- If word is invalid: opponent loses turn, tiles returned
- If word is valid: challenger loses next turn
- Configurable: some games auto-validate (no challenges)

**Configurable:**
- `enable_challenges`: Whether challenges are allowed (default: false for auto-validate)

### 6. Time Limit Rule (Optional)
**Each turn has a time limit.**

- If time expires: auto-pass or auto-resign
- Configurable per game

**Configurable:**
- `turn_time_limit`: Seconds per turn (default: null = no limit)
- `time_limit_action`: 'pass' or 'resign' (default: 'pass')

---

## Scoring Rules

### 1. Letter Points Rule
**Each letter has a point value.**

Dutch letter values:
| Points | Letters |
|--------|---------|
| 0 | Blank (wildcard) |
| 1 | E, N, A, O, I, D, R, S, T |
| 2 | G, K, L, M, P, U, B |
| 3 | W, H, F, J, V |
| 4 | C, Z |
| 5 | X |
| 8 | Q, Y |

English letter values:
| Points | Letters |
|--------|---------|
| 0 | Blank (wildcard) |
| 1 | E, A, I, O, N, R, T, L, S, U |
| 2 | D, G |
| 3 | B, C, M, P |
| 4 | F, H, V, W, Y |
| 5 | K |
| 8 | J, X |
| 10 | Q, Z |

### 2. Letter Multiplier Rule
**DL and TL squares multiply individual letter scores.**

- **DL (Double Letter)**: Letter points × 2
- **TL (Triple Letter)**: Letter points × 3
- Only applies on the turn the square is first covered
- Multiplier consumed after use

### 3. Word Multiplier Rule
**DW and TW squares multiply entire word scores.**

- **DW (Double Word)**: Word total × 2
- **TW (Triple Word)**: Word total × 3
- Applied after letter multipliers
- Multiple word multipliers stack multiplicatively (DW + DW = ×4)
- Only applies on the turn the square is first covered

### 4. Multiplier Order Rule
**Multipliers are applied in specific order.**

1. Calculate base letter points
2. Apply letter multipliers (DL, TL)
3. Sum all letters in word
4. Apply word multipliers (DW, TW)

### 5. Cross-Word Scoring Rule
**All newly formed words score points.**

- Main word scores with all multipliers
- Each cross-word scores independently
- Cross-words also get multipliers from newly placed tiles

### 6. Bingo Bonus Rule
**Using all 7 tiles in one turn earns a bonus.**

- Bonus: +50 points
- Must place exactly 7 tiles from rack
- Added after all word scores calculated

**Configurable:**
- `bingo_bonus`: Points for 7-tile play (default: 50)
- `bingo_tile_count`: Tiles required for bonus (default: 7)

---

## End Game Rules

### 1. Empty Rack Rule
**Game ends when a player empties their rack and bag is empty.**

- Player must use all tiles in rack
- Tile bag must be empty
- Player who empties rack wins tiebreaker

### 2. Consecutive Pass Rule
**Game ends after consecutive passes.**

- Default: 4 consecutive passes (2 per player)
- Wordfeud uses: 4 total passes
- Scrabble official: 6 passes (3 per player)

**Configurable:**
- `consecutive_pass_limit`: Passes before game ends (default: 4)

### 3. Resignation Rule
**Player can resign at any time.**

- Resigning player forfeits
- Opponent declared winner
- No final scoring adjustments

### 4. Final Score Adjustment Rule
**End game scoring adjustments.**

When game ends (not by resignation):
1. Each player subtracts sum of unplayed tile values
2. If one player emptied rack: add opponent's unplayed tile sum to their score

```
Player A (emptied rack): +15 (opponent's remaining tiles)
Player B (has tiles left): -15 (their remaining tiles)
```

### 5. Winner Determination Rule
**Highest score wins.**

- Player with highest final score wins
- Ties: player who made last move wins (emptied rack first)

---

## Board Configuration

### Standard 15×15 Board

```
    0  1  2  3  4  5  6  7  8  9 10 11 12 13 14
 0 TW __ __ DL __ __ __ TW __ __ __ DL __ __ TW
 1 __ DW __ __ __ TL __ __ __ TL __ __ __ DW __
 2 __ __ DW __ __ __ DL __ DL __ __ __ DW __ __
 3 DL __ __ DW __ __ __ DL __ __ __ DW __ __ DL
 4 __ __ __ __ DW __ __ __ __ __ DW __ __ __ __
 5 __ TL __ __ __ TL __ __ __ TL __ __ __ TL __
 6 __ __ DL __ __ __ DL __ DL __ __ __ DL __ __
 7 TW __ __ DL __ __ __ DW __ __ __ DL __ __ TW
 8 __ __ DL __ __ __ DL __ DL __ __ __ DL __ __
 9 __ TL __ __ __ TL __ __ __ TL __ __ __ TL __
10 __ __ __ __ DW __ __ __ __ __ DW __ __ __ __
11 DL __ __ DW __ __ __ DL __ __ __ DW __ __ DL
12 __ __ DW __ __ __ DL __ DL __ __ __ DW __ __
13 __ DW __ __ __ TL __ __ __ TL __ __ __ DW __
14 TW __ __ DL __ __ __ TW __ __ __ DL __ __ TW
```

### Multiplier Positions

**Triple Word (TW)** - 8 squares:
```php
(0,0), (0,7), (0,14), (7,0), (7,14), (14,0), (14,7), (14,14)
```

**Double Word (DW)** - 17 squares:
```php
(1,1), (2,2), (3,3), (4,4), (7,7), // diagonal top-left + center
(1,13), (2,12), (3,11), (4,10),    // diagonal top-right
(10,4), (11,3), (12,2), (13,1),    // diagonal bottom-left
(10,10), (11,11), (12,12), (13,13) // diagonal bottom-right
```

**Triple Letter (TL)** - 12 squares:
```php
(1,5), (1,9), (5,1), (5,5), (5,9), (5,13),
(9,1), (9,5), (9,9), (9,13), (13,5), (13,9)
```

**Double Letter (DL)** - 24 squares:
```php
(0,3), (0,11), (2,6), (2,8), (3,0), (3,7), (3,14),
(6,2), (6,6), (6,8), (6,12), (7,3), (7,11),
(8,2), (8,6), (8,8), (8,12), (11,0), (11,7), (11,14),
(12,6), (12,8), (14,3), (14,11)
```

---

## PHP Implementation

### Rule Interface

```php
<?php

namespace App\Rules\Contracts;

use App\Domain\Game\Models\Game;

interface RuleInterface
{
    /**
     * Get the rule identifier.
     */
    public function getIdentifier(): string;

    /**
     * Get human-readable rule name.
     */
    public function getName(): string;

    /**
     * Get rule description.
     */
    public function getDescription(): string;

    /**
     * Check if rule is enabled for this game.
     */
    public function isEnabled(Game $game): bool;
}
```

### Turn Rule Interface

```php
<?php

namespace App\Rules\Contracts;

use App\Domain\Game\Data\Move;use App\Domain\Game\Models\Game;use App\Domain\Game\Support\Rules\RuleResult;

interface TurnRuleInterface extends RuleInterface
{
    /**
     * Validate the move against this rule.
     *
     * @return RuleResult Success or failure with message
     */
    public function validate(Game $game, Move $move): RuleResult;
}
```

### Game Rule Interface

```php
<?php

namespace App\Rules\Contracts;

use App\Domain\Game\Models\Game;use App\Domain\Game\Support\Rules\RuleResult;use App\Domain\User\Models\User;

interface GameRuleInterface extends RuleInterface
{
    /**
     * Check if an action is allowed.
     */
    public function isActionAllowed(Game $game, User $player, string $action): RuleResult;
}
```

### Scoring Rule Interface

```php
<?php

namespace App\Rules\Contracts;

use App\Domain\Game\Data\Move;use App\Domain\Game\Data\Score;use App\Domain\Game\Models\Game;

interface ScoringRuleInterface extends RuleInterface
{
    /**
     * Calculate score contribution from this rule.
     */
    public function calculateScore(Game $game, Move $move, Score $currentScore): Score;
}
```

### End Game Rule Interface

```php
<?php

namespace App\Rules\Contracts;

use App\Domain\Game\Models\Game;

interface EndGameRuleInterface extends RuleInterface
{
    /**
     * Check if game should end based on this rule.
     */
    public function shouldEndGame(Game $game): bool;

    /**
     * Get the reason for game ending.
     */
    public function getEndReason(): string;
}
```

### Rule Result DTO

```php
<?php

namespace App\Rules;

readonly class RuleResult
{
    public function __construct(
        public bool $passed,
        public ?string $message = null,
        public ?string $ruleIdentifier = null,
    ) {}

    public static function pass(): self
    {
        return new self(passed: true);
    }

    public static function fail(string $message, string $ruleIdentifier): self
    {
        return new self(
            passed: false,
            message: $message,
            ruleIdentifier: $ruleIdentifier,
        );
    }
}
```

### Example Turn Rule: Line Placement

```php
<?php

namespace App\Rules\Turn;

use App\Domain\Game\Data\Move;use App\Domain\Game\Models\Game;use App\Domain\Game\Support\Rules\RuleResult;use App\Rules\Contracts\TurnRule;

class LinePlacementRule implements TurnRule
{
    public function getIdentifier(): string
    {
        return 'line_placement';
    }

    public function getName(): string
    {
        return 'Line Placement';
    }

    public function getDescription(): string
    {
        return 'All tiles must be placed in a single straight line (horizontal or vertical).';
    }

    public function isEnabled(Game $game): bool
    {
        return true; // Always enabled
    }

    public function validate(Game $game, Move $move): RuleResult
    {
        $tiles = $move->tiles;

        // Single tile is always valid
        if (count($tiles) <= 1) {
            return RuleResult::pass();
        }

        $xs = array_unique(array_column($tiles, 'x'));
        $ys = array_unique(array_column($tiles, 'y'));

        $isHorizontal = count($ys) === 1;
        $isVertical = count($xs) === 1;

        if (!$isHorizontal && !$isVertical) {
            return RuleResult::fail(
                'Tiles must be placed in a straight line.',
                $this->getIdentifier()
            );
        }

        return RuleResult::pass();
    }
}
```

### Example Turn Rule: No Gaps

```php
<?php

namespace App\Rules\Turn;

use App\Domain\Game\Data\Move;use App\Domain\Game\Models\Game;use App\Domain\Game\Support\Rules\RuleResult;use App\Rules\Contracts\TurnRule;

class NoGapsRule implements TurnRule
{
    public function getIdentifier(): string
    {
        return 'no_gaps';
    }

    public function getName(): string
    {
        return 'No Gaps';
    }

    public function getDescription(): string
    {
        return 'No empty cells allowed between placed tiles.';
    }

    public function isEnabled(Game $game): bool
    {
        return true;
    }

    public function validate(Game $game, Move $move): RuleResult
    {
        $tiles = $move->tiles;

        if (count($tiles) <= 1) {
            return RuleResult::pass();
        }

        $board = $game->board;
        $xs = array_column($tiles, 'x');
        $ys = array_column($tiles, 'y');

        $isHorizontal = count(array_unique($ys)) === 1;

        if ($isHorizontal) {
            $y = $ys[0];
            $minX = min($xs);
            $maxX = max($xs);

            for ($x = $minX; $x <= $maxX; $x++) {
                $hasPendingTile = in_array($x, $xs);
                $hasExistingTile = isset($board[$y][$x]);

                if (!$hasPendingTile && !$hasExistingTile) {
                    return RuleResult::fail(
                        'Gap detected at position (' . $x . ', ' . $y . ').',
                        $this->getIdentifier()
                    );
                }
            }
        } else {
            $x = $xs[0];
            $minY = min($ys);
            $maxY = max($ys);

            for ($y = $minY; $y <= $maxY; $y++) {
                $hasPendingTile = in_array($y, $ys);
                $hasExistingTile = isset($board[$y][$x]);

                if (!$hasPendingTile && !$hasExistingTile) {
                    return RuleResult::fail(
                        'Gap detected at position (' . $x . ', ' . $y . ').',
                        $this->getIdentifier()
                    );
                }
            }
        }

        return RuleResult::pass();
    }
}
```

### Example Game Rule: Swap Limit

```php
<?php

namespace App\Rules\Game;

use App\Domain\Game\Models\Game;use App\Domain\Game\Support\Rules\RuleResult;use App\Domain\User\Models\User;use App\Rules\Contracts\GameRule;

class SwapLimitRule implements GameRule
{
    public function __construct(
        private ?int $maxSwaps = null, // null = unlimited
        private int $minimumBagTiles = 7,
    ) {}

    public function getIdentifier(): string
    {
        return 'swap_limit';
    }

    public function getName(): string
    {
        return 'Swap Limit';
    }

    public function getDescription(): string
    {
        if ($this->maxSwaps === null) {
            return 'Unlimited swaps allowed when bag has at least ' . $this->minimumBagTiles . ' tiles.';
        }
        return 'Maximum ' . $this->maxSwaps . ' swaps per player per game.';
    }

    public function isEnabled(Game $game): bool
    {
        return true;
    }

    public function isActionAllowed(Game $game, User $player, string $action): RuleResult
    {
        if ($action !== 'swap') {
            return RuleResult::pass();
        }

        // Check minimum bag tiles
        if ($game->tiles_remaining < $this->minimumBagTiles) {
            return RuleResult::fail(
                'Cannot swap: tile bag has fewer than ' . $this->minimumBagTiles . ' tiles.',
                $this->getIdentifier()
            );
        }

        // Check swap limit
        if ($this->maxSwaps !== null) {
            $playerSwaps = $game->moves()
                ->where('user_id', $player->id)
                ->where('type', 'swap')
                ->count();

            if ($playerSwaps >= $this->maxSwaps) {
                return RuleResult::fail(
                    'Swap limit reached (' . $this->maxSwaps . ' per game).',
                    $this->getIdentifier()
                );
            }
        }

        return RuleResult::pass();
    }
}
```

### Example Scoring Rule: Bingo Bonus

```php
<?php

namespace App\Rules\Scoring;

use App\Domain\Game\Data\Move;use App\Domain\Game\Data\Score;use App\Domain\Game\Models\Game;use App\Rules\Contracts\ScoringRuleInterface;

class BingoBonusRule implements ScoringRuleInterface
{
    public function __construct(
        private int $bonusPoints = 50,
        private int $requiredTiles = 7,
    ) {}

    public function getIdentifier(): string
    {
        return 'bingo_bonus';
    }

    public function getName(): string
    {
        return 'Bingo Bonus';
    }

    public function getDescription(): string
    {
        return 'Earn ' . $this->bonusPoints . ' bonus points for using all ' . $this->requiredTiles . ' tiles.';
    }

    public function isEnabled(Game $game): bool
    {
        return true;
    }

    public function calculateScore(Game $game, Move $move, Score $currentScore): Score
    {
        if (count($move->tiles) === $this->requiredTiles) {
            return $currentScore->addBonus($this->bonusPoints, 'bingo');
        }

        return $currentScore;
    }
}
```

### Example End Game Rule: Consecutive Passes

```php
<?php

namespace App\Rules\EndGame;

use App\Domain\Game\Models\Game;use App\Rules\Contracts\EndGameRule;

class ConsecutivePassRule implements EndGameRule
{
    private bool $shouldEnd = false;

    public function __construct(
        private int $passLimit = 4,
    ) {}

    public function getIdentifier(): string
    {
        return 'consecutive_passes';
    }

    public function getName(): string
    {
        return 'Consecutive Passes';
    }

    public function getDescription(): string
    {
        return 'Game ends after ' . $this->passLimit . ' consecutive passes.';
    }

    public function isEnabled(Game $game): bool
    {
        return true;
    }

    public function shouldEndGame(Game $game): bool
    {
        $recentMoves = $game->moves()
            ->orderByDesc('id')
            ->take($this->passLimit)
            ->pluck('type')
            ->toArray();

        if (count($recentMoves) < $this->passLimit) {
            return false;
        }

        $this->shouldEnd = collect($recentMoves)->every(fn($type) => $type === 'pass');

        return $this->shouldEnd;
    }

    public function getEndReason(): string
    {
        return $this->passLimit . ' consecutive passes';
    }
}
```

### Rule Engine

```php
<?php

namespace App\Rules;

use App\Domain\Game\Data\Move;use App\Domain\Game\Models\Game;use App\Domain\Game\Support\Rules\RuleResult;use App\Rules\Contracts\EndGameRule;use App\Rules\Contracts\GameRule;use App\Rules\Contracts\ScoringRuleInterface;use App\Rules\Contracts\TurnRule;

class RuleEngine
{
    /** @var TurnRule[] */
    private array $turnRules = [];

    /** @var GameRule[] */
    private array $gameRules = [];

    /** @var ScoringRuleInterface[] */
    private array $scoringRules = [];

    /** @var EndGameRule[] */
    private array $endGameRules = [];

    public function addTurnRule(TurnRule $rule): self
    {
        $this->turnRules[] = $rule;
        return $this;
    }

    public function addGameRule(GameRule $rule): self
    {
        $this->gameRules[] = $rule;
        return $this;
    }

    public function addScoringRule(ScoringRuleInterface $rule): self
    {
        $this->scoringRules[] = $rule;
        return $this;
    }

    public function addEndGameRule(EndGameRule $rule): self
    {
        $this->endGameRules[] = $rule;
        return $this;
    }

    /**
     * Validate a move against all turn rules.
     *
     * @return RuleResult[] Array of failed rules (empty if all passed)
     */
    public function validateMove(Game $game, Move $move): array
    {
        $failures = [];

        foreach ($this->turnRules as $rule) {
            if (!$rule->isEnabled($game)) {
                continue;
            }

            $result = $rule->validate($game, $move);

            if (!$result->passed) {
                $failures[] = $result;
            }
        }

        return $failures;
    }

    /**
     * Check if game should end.
     *
     * @return EndGameRule|null The rule that triggered end, or null
     */
    public function checkEndGame(Game $game): ?EndGameRule
    {
        foreach ($this->endGameRules as $rule) {
            if (!$rule->isEnabled($game)) {
                continue;
            }

            if ($rule->shouldEndGame($game)) {
                return $rule;
            }
        }

        return null;
    }
}
```

### Rule Configuration (Service Provider)

```php
<?php

namespace App\Providers;

use ;use ;use ;use ;use App\Domain\Game\Support\Rules\RuleEngine;use Illuminate\Support\ServiceProvider;
*;
*;
*;
*;

class RuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(RuleEngine::class, function () {
            $engine = new RuleEngine();

            // Turn Rules
            $engine->addTurnRule(new LinePlacementRule());
            $engine->addTurnRule(new NoGapsRule());
            $engine->addTurnRule(new BoardBoundsRule());
            $engine->addTurnRule(new CellAvailabilityRule());
            $engine->addTurnRule(new FirstMoveCenterRule());
            $engine->addTurnRule(new ConnectionRule());
            $engine->addTurnRule(new WordValidationRule());
            $engine->addTurnRule(new TilesInRackRule());

            // Game Rules
            $engine->addGameRule(new RackSizeRule(maxSize: 7));
            $engine->addGameRule(new SwapLimitRule(maxSwaps: null, minimumBagTiles: 7));
            $engine->addGameRule(new PassRule(allowPass: true));

            // Scoring Rules
            $engine->addScoringRule(new LetterPointsRule());
            $engine->addScoringRule(new LetterMultiplierRule());
            $engine->addScoringRule(new WordMultiplierRule());
            $engine->addScoringRule(new CrossWordScoringRule());
            $engine->addScoringRule(new BingoBonusRule(bonusPoints: 50));

            // End Game Rules
            $engine->addEndGameRule(new EmptyRackRule());
            $engine->addEndGameRule(new ConsecutivePassRule(passLimit: 4));
            $engine->addEndGameRule(new ResignationRule());

            return $engine;
        });
    }
}
```

---

## Directory Structure

```
app/
├── DTOs/
│   ├── MoveData.php
│   └── ScoreData.php
├── Rules/
│   ├── Contracts/
│   │   ├── RuleInterface.php
│   │   ├── TurnRuleInterface.php
│   │   ├── GameRuleInterface.php
│   │   ├── ScoringRuleInterface.php
│   │   └── EndGameRuleInterface.php
│   ├── Turn/
│   │   ├── LinePlacementRule.php
│   │   ├── NoGapsRule.php
│   │   ├── BoardBoundsRule.php
│   │   ├── CellAvailabilityRule.php
│   │   ├── FirstMoveCenterRule.php
│   │   ├── ConnectionRule.php
│   │   ├── WordValidationRule.php
│   │   └── TilesInRackRule.php
│   ├── Game/
│   │   ├── RackSizeRule.php
│   │   ├── SwapLimitRule.php
│   │   ├── PassRule.php
│   │   └── TurnOrderRule.php
│   ├── Scoring/
│   │   ├── LetterPointsRule.php
│   │   ├── LetterMultiplierRule.php
│   │   ├── WordMultiplierRule.php
│   │   ├── CrossWordScoringRule.php
│   │   └── BingoBonusRule.php
│   ├── EndGame/
│   │   ├── EmptyRackRule.php
│   │   ├── ConsecutivePassRule.php
│   │   ├── ResignationRule.php
│   │   └── FinalScoreAdjustmentRule.php
│   ├── RuleEngine.php
│   └── RuleResult.php
└── Providers/
    └── RuleServiceProvider.php
```

---

## Customization Examples

### House Rules Variant

```php
// More forgiving rules for casual play
$engine->addGameRule(new SwapLimitRule(maxSwaps: null, minimumBagTiles: 1));
$engine->addEndGameRule(new ConsecutivePassRule(passLimit: 6));
$engine->addScoringRule(new BingoBonusRule(bonusPoints: 35));
```

### Speed Game Variant

```php
// Fast-paced game with time limits
$engine->addGameRule(new TimeLimitRule(
    secondsPerTurn: 60,
    timeoutAction: 'pass'
));
$engine->addEndGameRule(new ConsecutivePassRule(passLimit: 2));
```

### Challenge Mode

```php
// Enable word challenges
$engine->addGameRule(new ChallengeRule(
    enabled: true,
    penaltyForInvalidChallenge: 'lose_turn'
));
```
