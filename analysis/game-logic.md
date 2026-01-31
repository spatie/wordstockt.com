# WordStockt Game Logic

## Board Configuration

### Board Size
- 15x15 grid (225 cells)
- Center cell at position (7, 7)

### Multiplier Cells

```
    0   1   2   3   4   5   6   7   8   9  10  11  12  13  14
  ┌───┬───┬───┬───┬───┬───┬───┬───┬───┬───┬───┬───┬───┬───┬───┐
0 │TW │   │   │DL │   │   │   │TW │   │   │   │DL │   │   │TW │
  ├───┼───┼───┼───┼───┼───┼───┼───┼───┼───┼───┼───┼───┼───┼───┤
1 │   │DW │   │   │   │TL │   │   │   │TL │   │   │   │DW │   │
  ├───┼───┼───┼───┼───┼───┼───┼───┼───┼───┼───┼───┼───┼───┼───┤
2 │   │   │DW │   │   │   │DL │   │DL │   │   │   │DW │   │   │
  ├───┼───┼───┼───┼───┼───┼───┼───┼───┼───┼───┼───┼───┼───┼───┤
3 │DL │   │   │DW │   │   │   │DL │   │   │   │DW │   │   │DL │
  ├───┼───┼───┼───┼───┼───┼───┼───┼───┼───┼───┼───┼───┼───┼───┤
4 │   │   │   │   │DW │   │   │   │   │   │DW │   │   │   │   │
  ├───┼───┼───┼───┼───┼───┼───┼───┼───┼───┼───┼───┼───┼───┼───┤
5 │   │TL │   │   │   │TL │   │   │   │TL │   │   │   │TL │   │
  ├───┼───┼───┼───┼───┼───┼───┼───┼───┼───┼───┼───┼───┼───┼───┤
6 │   │   │DL │   │   │   │DL │   │DL │   │   │   │DL │   │   │
  ├───┼───┼───┼───┼───┼───┼───┼───┼───┼───┼───┼───┼───┼───┼───┤
7 │TW │   │   │DL │   │   │   │DW │   │   │   │DL │   │   │TW │
  ├───┼───┼───┼───┼───┼───┼───┼───┼───┼───┼───┼───┼───┼───┼───┤
8 │   │   │DL │   │   │   │DL │   │DL │   │   │   │DL │   │   │
  ├───┼───┼───┼───┼───┼───┼───┼───┼───┼───┼───┼───┼───┼───┼───┤
9 │   │TL │   │   │   │TL │   │   │   │TL │   │   │   │TL │   │
  ├───┼───┼───┼───┼───┼───┼───┼───┼───┼───┼───┼───┼───┼───┼───┤
10│   │   │   │   │DW │   │   │   │   │   │DW │   │   │   │   │
  ├───┼───┼───┼───┼───┼───┼───┼───┼───┼───┼───┼───┼───┼───┼───┤
11│DL │   │   │DW │   │   │   │DL │   │   │   │DW │   │   │DL │
  ├───┼───┼───┼───┼───┼───┼───┼───┼───┼───┼───┼───┼───┼───┼───┤
12│   │   │DW │   │   │   │DL │   │DL │   │   │   │DW │   │   │
  ├───┼───┼───┼───┼───┼───┼───┼───┼───┼───┼───┼───┼───┼───┼───┤
13│   │DW │   │   │   │TL │   │   │   │TL │   │   │   │DW │   │
  ├───┼───┼───┼───┼───┼───┼───┼───┼───┼───┼───┼───┼───┼───┼───┤
14│TW │   │   │DL │   │   │   │TW │   │   │   │DL │   │   │TW │
  └───┴───┴───┴───┴───┴───┴───┴───┴───┴───┴───┴───┴───┴───┴───┘
```

**Legend:**
- `TW` = Triple Word (red) - Multiplies entire word by 3
- `DW` = Double Word (pink) - Multiplies entire word by 2
- `TL` = Triple Letter (blue) - Multiplies letter by 3
- `DL` = Double Letter (light blue) - Multiplies letter by 2

## Tile Distribution

### Dutch (nl) - 102 tiles total
| Letter | Count | Points |
|--------|-------|--------|
| A | 6 | 1 |
| B | 2 | 3 |
| C | 2 | 5 |
| D | 5 | 2 |
| E | 18 | 1 |
| F | 2 | 4 |
| G | 3 | 3 |
| H | 2 | 4 |
| I | 4 | 1 |
| J | 2 | 4 |
| K | 3 | 3 |
| L | 3 | 3 |
| M | 3 | 3 |
| N | 10 | 1 |
| O | 6 | 1 |
| P | 2 | 3 |
| Q | 1 | 10 |
| R | 5 | 2 |
| S | 5 | 2 |
| T | 5 | 2 |
| U | 3 | 4 |
| V | 2 | 4 |
| W | 2 | 5 |
| X | 1 | 8 |
| Y | 1 | 8 |
| Z | 2 | 4 |
| * (blank) | 2 | 0 |

### English (en) - 100 tiles total
Similar distribution with adjusted counts for English letter frequency.

## Game Rules

### Starting the Game
1. Each player draws 7 tiles from the bag
2. First player must cover the center cell (7,7)
3. First word must be at least 2 letters

### Valid Moves
A move is valid if:
1. **Linear placement**: All tiles in single row OR column
2. **No gaps**: Continuous line (can include existing tiles)
3. **Connected**: Must connect to existing tiles (except first move)
4. **Valid words**: All formed words must exist in dictionary

### Move Types
| Type | Description |
|------|-------------|
| **Play** | Place tiles on board |
| **Pass** | Skip turn (no points) |
| **Swap** | Exchange tiles with bag (skip turn) |
| **Resign** | Forfeit the game |

### Scoring

#### Letter Scoring
```
Word Score = Σ(letter_points × letter_multiplier) × word_multiplier
```

1. Calculate each letter's points (blank = 0)
2. Apply letter multipliers (DL, TL) to newly placed tiles
3. Sum all letter scores
4. Apply word multipliers (DW, TW) to newly placed tiles
5. **Multipliers stack**: If placing on multiple DW cells, multiply accordingly

#### Bingo Bonus
- **+50 points** for using all 7 tiles in one turn

#### Example Score Calculation
```
Playing "WORD" where:
- W (5pts) on TL = 15
- O (1pt) normal = 1
- R (2pts) normal = 2
- D (2pts) on DW cell

Word score: (15 + 1 + 2 + 2) × 2 = 40 points
```

### Game End Conditions
1. **Player empties rack** when tile bag is empty
2. **Four consecutive passes** (2 from each player)
3. **Player resigns**

### End Game Scoring
- Subtract remaining tile points from each player's score
- Player with highest score wins

## Validation Flow

```
submitMove(tiles)
├── validateCanPlay()
│   ├── Check game is active
│   └── Check it's user's turn
├── validateTilesInRack()
│   └── Verify all placed tiles exist in player's rack
├── validatePlacement()
│   ├── Check bounds (0-14)
│   ├── Check positions empty
│   ├── Check single line (row or column)
│   ├── Check no gaps
│   └── Check connected (or first move covers center)
├── findFormedWords()
│   └── Get main word + perpendicular words
└── assertWordsValid()
    └── Check all words exist in dictionary
```

## Dictionary

### Import Command
```bash
php artisan dictionary:import nl  # Dutch
php artisan dictionary:import en  # English
```

### Sources
- **Dutch**: OpenTaal (~400k words)
- **English**: Standard word list

### Validation
Words are validated against the `dictionaries` table:
```php
SELECT EXISTS(
    SELECT 1 FROM dictionaries
    WHERE word = ? AND language = ?
)
```
