# WordStockt Testing Strategy

This document outlines testing approaches for both the Laravel backend and React Native frontend.

---

## Table of Contents

1. [Laravel Backend Testing](#laravel-backend-testing)
2. [React Native Testing](#react-native-testing)
3. [End-to-End Testing](#end-to-end-testing)
4. [Test Data & Factories](#test-data--factories)

---

## Laravel Backend Testing

### Directory Structure

```
tests/
├── Feature/
│   ├── Auth/
│   │   ├── LoginTest.php
│   │   ├── RegisterTest.php
│   │   └── LogoutTest.php
│   ├── Game/
│   │   ├── CreateGameTest.php
│   │   ├── JoinGameTest.php
│   │   ├── PlayMoveTest.php
│   │   ├── PassTurnTest.php
│   │   └── SwapTilesTest.php
│   └── Message/
│       └── GameChatTest.php
├── Unit/
│   ├── Services/
│   │   ├── GameServiceTest.php
│   │   ├── BoardServiceTest.php
│   │   ├── ScoringServiceTest.php
│   │   ├── TileServiceTest.php
│   │   └── DictionaryServiceTest.php
│   └── Rules/
│       ├── Turn/
│       │   ├── LinePlacementRuleTest.php
│       │   ├── NoGapsRuleTest.php
│       │   └── ConnectionRuleTest.php
│       └── Scoring/
│           └── BingoBonusRuleTest.php
└── TestCase.php
```

### PHPUnit Configuration

```xml
<!-- phpunit.xml -->
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true">
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory>tests/Feature</directory>
        </testsuite>
    </testsuites>
    <source>
        <include>
            <directory>app</directory>
        </include>
    </source>
    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="DB_CONNECTION" value="sqlite"/>
        <env name="DB_DATABASE" value=":memory:"/>
        <env name="BCRYPT_ROUNDS" value="4"/>
        <env name="CACHE_DRIVER" value="array"/>
        <env name="QUEUE_CONNECTION" value="sync"/>
        <env name="SESSION_DRIVER" value="array"/>
    </php>
</phpunit>
```

### Unit Tests for Services

#### GameService Test

```php
<?php

namespace Tests\Unit\Services;

use App\Domain\Game\Enums\GameStatus;use App\Domain\Game\Models\Game;use App\Domain\User\Models\User;use App\Services\GameService;use Illuminate\Foundation\Testing\RefreshDatabase;use Tests\TestCase;

class GameServiceTest extends TestCase
{
    use RefreshDatabase;

    private GameService $gameService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gameService = app(GameService::class);
    }

    /** @test */
    public function it_creates_a_game_for_user(): void
    {
        $user = User::factory()->create();

        $game = $this->gameService->createGame($user, 'nl');

        $this->assertInstanceOf(Game::class, $game);
        $this->assertEquals(GameStatus::Pending, $game->status);
        $this->assertEquals('nl', $game->language);
        $this->assertTrue($game->players->contains('user_id', $user->id));
    }

    /** @test */
    public function it_creates_a_game_with_opponent(): void
    {
        $creator = User::factory()->create();
        $opponent = User::factory()->create();

        $game = $this->gameService->createGame($creator, 'nl', $opponent);

        $this->assertEquals(GameStatus::Active, $game->status);
        $this->assertCount(2, $game->players);
    }

    /** @test */
    public function it_allows_joining_pending_game(): void
    {
        $creator = User::factory()->create();
        $joiner = User::factory()->create();
        $game = $this->gameService->createGame($creator, 'nl');

        $this->gameService->joinGame($game, $joiner);

        $game->refresh();
        $this->assertEquals(GameStatus::Active, $game->status);
        $this->assertCount(2, $game->players);
    }

    /** @test */
    public function it_prevents_joining_active_game(): void
    {
        $creator = User::factory()->create();
        $opponent = User::factory()->create();
        $thirdUser = User::factory()->create();

        $game = $this->gameService->createGame($creator, 'nl', $opponent);

        $this->expectException(\App\Domain\Game\Exceptions\GameException::class);
        $this->gameService->joinGame($game, $thirdUser);
    }
}
```

#### BoardService Test

```php
<?php

namespace Tests\Unit\Services;

use App\Domain\Game\Support\Board;use Tests\TestCase;

class BoardServiceTest extends TestCase
{
    private Board $boardService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->boardService = new Board();
    }

    /** @test */
    public function it_validates_tiles_in_straight_line(): void
    {
        $tiles = [
            ['x' => 7, 'y' => 7, 'letter' => 'H'],
            ['x' => 8, 'y' => 7, 'letter' => 'I'],
        ];

        $result = $this->boardService->validatePlacement($tiles, []);

        $this->assertTrue($result['valid']);
    }

    /** @test */
    public function it_rejects_tiles_not_in_line(): void
    {
        $tiles = [
            ['x' => 7, 'y' => 7, 'letter' => 'H'],
            ['x' => 8, 'y' => 8, 'letter' => 'I'], // Diagonal
        ];

        $result = $this->boardService->validatePlacement($tiles, []);

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('line', $result['error']);
    }

    /** @test */
    public function it_detects_gaps_in_placement(): void
    {
        $tiles = [
            ['x' => 7, 'y' => 7, 'letter' => 'H'],
            ['x' => 9, 'y' => 7, 'letter' => 'I'], // Gap at x=8
        ];
        $board = []; // Empty board

        $result = $this->boardService->validatePlacement($tiles, $board);

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('gap', strtolower($result['error']));
    }

    /** @test */
    public function it_requires_first_move_covers_center(): void
    {
        $tiles = [
            ['x' => 0, 'y' => 0, 'letter' => 'H'],
            ['x' => 1, 'y' => 0, 'letter' => 'I'],
        ];
        $board = []; // Empty board = first move

        $result = $this->boardService->validatePlacement($tiles, $board);

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('center', strtolower($result['error']));
    }
}
```

#### ScoringService Test

```php
<?php

namespace Tests\Unit\Services;

use App\Support\ScoringService;use Tests\TestCase;

class ScoringServiceTest extends TestCase
{
    private ScoringService $scoringService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scoringService = new ScoringService();
    }

    /** @test */
    public function it_calculates_basic_word_score(): void
    {
        $tiles = [
            ['letter' => 'H', 'points' => 4, 'x' => 7, 'y' => 7],
            ['letter' => 'I', 'points' => 1, 'x' => 8, 'y' => 7],
        ];
        $boardTemplate = $this->getEmptyTemplate();

        $score = $this->scoringService->calculateScore($tiles, [], $boardTemplate);

        $this->assertEquals(5, $score['total']); // H(4) + I(1)
    }

    /** @test */
    public function it_applies_double_letter_multiplier(): void
    {
        $tiles = [
            ['letter' => 'H', 'points' => 4, 'x' => 0, 'y' => 3], // DL position
        ];
        $boardTemplate = $this->getStandardTemplate();

        $score = $this->scoringService->calculateScore($tiles, [], $boardTemplate);

        $this->assertEquals(8, $score['total']); // H(4) × 2
    }

    /** @test */
    public function it_applies_triple_word_multiplier(): void
    {
        $tiles = [
            ['letter' => 'H', 'points' => 4, 'x' => 0, 'y' => 0], // TW position
            ['letter' => 'I', 'points' => 1, 'x' => 1, 'y' => 0],
        ];
        $boardTemplate = $this->getStandardTemplate();

        $score = $this->scoringService->calculateScore($tiles, [], $boardTemplate);

        $this->assertEquals(15, $score['total']); // (4 + 1) × 3
    }

    /** @test */
    public function it_adds_bingo_bonus_for_seven_tiles(): void
    {
        $tiles = array_map(fn($i) => [
            'letter' => 'A',
            'points' => 1,
            'x' => 7 + $i,
            'y' => 7,
        ], range(0, 6));

        $boardTemplate = $this->getEmptyTemplate();

        $score = $this->scoringService->calculateScore($tiles, [], $boardTemplate);

        $this->assertEquals(57, $score['total']); // 7 + 50 bonus
        $this->assertEquals(50, $score['bonus']);
    }

    private function getEmptyTemplate(): array
    {
        return array_fill(0, 15, array_fill(0, 15, null));
    }

    private function getStandardTemplate(): array
    {
        // Return the standard board template with multipliers
        // See game-logic.md for positions
    }
}
```

### Testing Rule Classes

```php
<?php

namespace Tests\Unit\Rules\Turn;

use App\Domain\Game\Data\Move;use App\Domain\Game\Models\Game;use App\Domain\Game\Support\Rules\Turn\LinePlacementRule;use Tests\TestCase;

class LinePlacementRuleTest extends TestCase
{
    private LinePlacementRule $rule;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rule = new LinePlacementRule();
    }

    /** @test */
    public function it_passes_for_horizontal_line(): void
    {
        $move = new Move(tiles: [
            ['x' => 7, 'y' => 7, 'letter' => 'A'],
            ['x' => 8, 'y' => 7, 'letter' => 'B'],
            ['x' => 9, 'y' => 7, 'letter' => 'C'],
        ]);

        $result = $this->rule->validate($this->mockGame(), $move);

        $this->assertTrue($result->passed);
    }

    /** @test */
    public function it_passes_for_vertical_line(): void
    {
        $move = new Move(tiles: [
            ['x' => 7, 'y' => 7, 'letter' => 'A'],
            ['x' => 7, 'y' => 8, 'letter' => 'B'],
            ['x' => 7, 'y' => 9, 'letter' => 'C'],
        ]);

        $result = $this->rule->validate($this->mockGame(), $move);

        $this->assertTrue($result->passed);
    }

    /** @test */
    public function it_fails_for_diagonal_placement(): void
    {
        $move = new Move(tiles: [
            ['x' => 7, 'y' => 7, 'letter' => 'A'],
            ['x' => 8, 'y' => 8, 'letter' => 'B'],
        ]);

        $result = $this->rule->validate($this->mockGame(), $move);

        $this->assertFalse($result->passed);
        $this->assertEquals('line_placement', $result->ruleIdentifier);
    }

    /** @test */
    public function it_passes_for_single_tile(): void
    {
        $move = new Move(tiles: [
            ['x' => 7, 'y' => 7, 'letter' => 'A'],
        ]);

        $result = $this->rule->validate($this->mockGame(), $move);

        $this->assertTrue($result->passed);
    }

    private function mockGame(): Game
    {
        return Game::factory()->make();
    }
}
```

### Feature Tests for API Endpoints

```php
<?php

namespace Tests\Feature\Auth;

use App\Domain\User\Models\User;use Illuminate\Foundation\Testing\RefreshDatabase;use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'user' => ['id', 'username', 'email'],
                'token',
            ]);
    }

    /** @test */
    public function login_fails_with_invalid_credentials(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertUnauthorized()
            ->assertJson(['message' => 'Invalid credentials']);
    }

    /** @test */
    public function login_validates_required_fields(): void
    {
        $response = $this->postJson('/api/auth/login', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password']);
    }
}
```

```php
<?php

namespace Tests\Feature\Game;

use App\Domain\Game\Models\Game;use App\Domain\User\Models\User;use Illuminate\Foundation\Testing\RefreshDatabase;use Tests\TestCase;

class PlayMoveTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function player_can_submit_valid_move(): void
    {
        $game = $this->createActiveGame();
        $currentPlayer = $game->currentPlayer;

        $response = $this->actingAs($currentPlayer->user)
            ->postJson("/api/games/{$game->id}/moves", [
                'tiles' => [
                    ['letter' => 'H', 'x' => 7, 'y' => 7, 'is_blank' => false],
                    ['letter' => 'I', 'x' => 8, 'y' => 7, 'is_blank' => false],
                ],
            ]);

        $response->assertOk()
            ->assertJsonStructure([
                'game',
                'move' => ['id', 'score', 'words'],
            ]);
    }

    /** @test */
    public function player_cannot_move_out_of_turn(): void
    {
        $game = $this->createActiveGame();
        $otherPlayer = $game->players->firstWhere('is_current_turn', false);

        $response = $this->actingAs($otherPlayer->user)
            ->postJson("/api/games/{$game->id}/moves", [
                'tiles' => [
                    ['letter' => 'H', 'x' => 7, 'y' => 7, 'is_blank' => false],
                ],
            ]);

        $response->assertForbidden()
            ->assertJson(['message' => 'Not your turn']);
    }

    /** @test */
    public function move_rejected_for_invalid_word(): void
    {
        $game = $this->createActiveGame();
        $currentPlayer = $game->currentPlayer;

        $response = $this->actingAs($currentPlayer->user)
            ->postJson("/api/games/{$game->id}/moves", [
                'tiles' => [
                    ['letter' => 'X', 'x' => 7, 'y' => 7, 'is_blank' => false],
                    ['letter' => 'Z', 'x' => 8, 'y' => 7, 'is_blank' => false],
                    ['letter' => 'Q', 'x' => 9, 'y' => 7, 'is_blank' => false],
                ],
            ]);

        $response->assertUnprocessable()
            ->assertJsonPath('error', 'Invalid word: XZQ');
    }

    private function createActiveGame(): Game
    {
        $creator = User::factory()->create();
        $opponent = User::factory()->create();

        return Game::factory()
            ->withPlayers($creator, $opponent)
            ->active()
            ->create();
    }
}
```

---

## React Native Testing

### Directory Structure

```
mobile/
├── __tests__/
│   ├── components/
│   │   ├── game/
│   │   │   ├── GameBoard.test.tsx
│   │   │   ├── BoardCell.test.tsx
│   │   │   └── Tile.test.tsx
│   │   └── ui/
│   │       └── GameButton.test.tsx
│   ├── hooks/
│   │   ├── useGame.test.ts
│   │   └── useAuth.test.ts
│   ├── stores/
│   │   ├── authStore.test.ts
│   │   └── gameStore.test.ts
│   └── utils/
│       └── scoring.test.ts
├── jest.config.js
└── jest.setup.js
```

### Jest Configuration

```javascript
// jest.config.js
module.exports = {
  preset: 'react-native',
  setupFilesAfterEnv: ['./jest.setup.js'],
  transformIgnorePatterns: [
    'node_modules/(?!(react-native|@react-native|react-native-paper|@react-navigation)/)',
  ],
  moduleNameMapper: {
    '^@/(.*)$': '<rootDir>/src/$1',
  },
  collectCoverageFrom: [
    'src/**/*.{ts,tsx}',
    '!src/**/*.d.ts',
    '!src/types/**',
  ],
  coverageThreshold: {
    global: {
      branches: 70,
      functions: 70,
      lines: 70,
      statements: 70,
    },
  },
};
```

```javascript
// jest.setup.js
import '@testing-library/react-native/extend-expect';
import { jest } from '@jest/globals';

// Mock AsyncStorage
jest.mock('@react-native-async-storage/async-storage', () =>
  require('@react-native-async-storage/async-storage/jest/async-storage-mock')
);

// Mock react-native-paper
jest.mock('react-native-paper', () => {
  const actual = jest.requireActual('react-native-paper');
  return {
    ...actual,
    Portal: ({ children }) => children,
  };
});

// Silence console warnings in tests
global.console = {
  ...console,
  warn: jest.fn(),
  error: jest.fn(),
};
```

### Component Testing

```typescript
// __tests__/components/game/Tile.test.tsx
import React from 'react';
import { render, fireEvent } from '@testing-library/react-native';
import { Tile } from '@/components/game/Tile';

describe('Tile', () => {
  it('renders letter and points', () => {
    const { getByText } = render(
      <Tile letter="H" points={4} />
    );

    expect(getByText('H')).toBeTruthy();
    expect(getByText('4')).toBeTruthy();
  });

  it('applies pending style when isPending is true', () => {
    const { getByTestId } = render(
      <Tile letter="H" points={4} isPending testID="tile" />
    );

    const tile = getByTestId('tile');
    expect(tile.props.style).toContainEqual(
      expect.objectContaining({ borderColor: '#FF6B35' })
    );
  });

  it('calls onRemove when remove button pressed', () => {
    const onRemove = jest.fn();
    const { getByText } = render(
      <Tile letter="H" points={4} isPending onRemove={onRemove} />
    );

    fireEvent.press(getByText('×'));
    expect(onRemove).toHaveBeenCalledTimes(1);
  });
});
```

```typescript
// __tests__/components/game/GameBoard.test.tsx
import React from 'react';
import { render, fireEvent } from '@testing-library/react-native';
import { GameBoard } from '@/components/game/GameBoard';
import { mockGame } from '../../mocks/game';

describe('GameBoard', () => {
  const defaultProps = {
    game: mockGame(),
    onCellPress: jest.fn(),
    isMyTurn: true,
  };

  it('renders 15x15 grid', () => {
    const { getAllByTestId } = render(<GameBoard {...defaultProps} />);

    const cells = getAllByTestId(/^cell-/);
    expect(cells).toHaveLength(225); // 15 × 15
  });

  it('calls onCellPress with coordinates when cell tapped', () => {
    const onCellPress = jest.fn();
    const { getByTestId } = render(
      <GameBoard {...defaultProps} onCellPress={onCellPress} />
    );

    fireEvent.press(getByTestId('cell-7-7'));
    expect(onCellPress).toHaveBeenCalledWith(7, 7);
  });

  it('disables interaction when not my turn', () => {
    const onCellPress = jest.fn();
    const { getByTestId } = render(
      <GameBoard {...defaultProps} onCellPress={onCellPress} isMyTurn={false} />
    );

    fireEvent.press(getByTestId('cell-7-7'));
    expect(onCellPress).not.toHaveBeenCalled();
  });
});
```

### Hook Testing

```typescript
// __tests__/hooks/useGame.test.ts
import { renderHook, waitFor } from '@testing-library/react-native';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { useGame } from '@/api/queries/useGame';
import { mockGame } from '../mocks/game';

// Mock API client
jest.mock('@/api/client', () => ({
  apiClient: {
    get: jest.fn(),
    post: jest.fn(),
  },
}));

import { apiClient } from '@/api/client';

const createWrapper = () => {
  const queryClient = new QueryClient({
    defaultOptions: {
      queries: { retry: false },
    },
  });
  return ({ children }) => (
    <QueryClientProvider client={queryClient}>
      {children}
    </QueryClientProvider>
  );
};

describe('useGame', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  it('fetches game data successfully', async () => {
    const game = mockGame();
    (apiClient.get as jest.Mock).mockResolvedValueOnce({ data: game });

    const { result } = renderHook(() => useGame(1), {
      wrapper: createWrapper(),
    });

    expect(result.current.isLoading).toBe(true);

    await waitFor(() => {
      expect(result.current.isSuccess).toBe(true);
    });

    expect(result.current.data?.id).toBe(game.id);
    expect(apiClient.get).toHaveBeenCalledWith('/games/1');
  });

  it('handles error response', async () => {
    (apiClient.get as jest.Mock).mockRejectedValueOnce(
      new Error('Network error')
    );

    const { result } = renderHook(() => useGame(1), {
      wrapper: createWrapper(),
    });

    await waitFor(() => {
      expect(result.current.isError).toBe(true);
    });

    expect(result.current.error?.message).toBe('Network error');
  });
});
```

### Store Testing

```typescript
// __tests__/stores/gameStore.test.ts
import { useGameStore } from '@/stores/gameStore';
import { act } from '@testing-library/react-native';

describe('gameStore', () => {
  beforeEach(() => {
    // Reset store between tests
    useGameStore.setState({
      pendingTiles: [],
      selectedRackIndex: null,
    });
  });

  it('places tile on board', () => {
    const tile = { letter: 'H', points: 4, isBlank: false };

    act(() => {
      useGameStore.getState().placeTile(tile, 7, 7, 0);
    });

    const state = useGameStore.getState();
    expect(state.pendingTiles).toHaveLength(1);
    expect(state.pendingTiles[0]).toMatchObject({
      letter: 'H',
      x: 7,
      y: 7,
      rackIndex: 0,
    });
    expect(state.selectedRackIndex).toBeNull();
  });

  it('removes tile from board', () => {
    const tile = { letter: 'H', points: 4, isBlank: false };

    act(() => {
      useGameStore.getState().placeTile(tile, 7, 7, 0);
      useGameStore.getState().removeTile(7, 7);
    });

    expect(useGameStore.getState().pendingTiles).toHaveLength(0);
  });

  it('recalls all tiles', () => {
    const tile = { letter: 'H', points: 4, isBlank: false };

    act(() => {
      useGameStore.getState().placeTile(tile, 7, 7, 0);
      useGameStore.getState().placeTile(tile, 8, 7, 1);
      useGameStore.getState().recallAllTiles();
    });

    expect(useGameStore.getState().pendingTiles).toHaveLength(0);
  });
});
```

### Test Mocks

```typescript
// __tests__/mocks/game.ts
import type { Game, Player, Tile } from '@/types';

export function mockGame(overrides: Partial<Game> = {}): Game {
  return {
    id: 1,
    language: 'nl',
    status: 'active',
    board: emptyBoard(),
    boardTemplate: standardTemplate(),
    players: [mockPlayer({ id: 1 }), mockPlayer({ id: 2 })],
    myRack: mockRack(),
    tilesRemaining: 86,
    currentTurnUserId: 1,
    winnerId: null,
    lastMove: null,
    ...overrides,
  };
}

export function mockPlayer(overrides: Partial<Player> = {}): Player {
  return {
    id: 1,
    username: 'testuser',
    score: 0,
    rackCount: 7,
    isCurrentTurn: true,
    ...overrides,
  };
}

export function mockRack(): Tile[] {
  return [
    { letter: 'H', points: 4, isBlank: false },
    { letter: 'E', points: 1, isBlank: false },
    { letter: 'L', points: 2, isBlank: false },
    { letter: 'L', points: 2, isBlank: false },
    { letter: 'O', points: 1, isBlank: false },
    { letter: 'A', points: 1, isBlank: false },
    { letter: 'N', points: 1, isBlank: false },
  ];
}

function emptyBoard(): (Tile | null)[][] {
  return Array(15).fill(null).map(() => Array(15).fill(null));
}

function standardTemplate(): (string | null)[][] {
  // Return standard multiplier template
  // See game-logic.md for positions
}
```

---

## End-to-End Testing

### Maestro (Recommended for Mobile)

Maestro is a simple, declarative E2E testing framework for mobile apps.

#### Installation

```bash
# macOS
brew install maestro

# Or download from https://maestro.mobile.dev
```

#### Test Flows

```yaml
# e2e/flows/login.yaml
appId: com.wordstockt.app
---
- launchApp
- assertVisible: "WordStockt"
- tapOn: "Email"
- inputText: "test@example.com"
- tapOn: "Password"
- inputText: "password"
- tapOn: "Login"
- assertVisible: "Your Games"
```

```yaml
# e2e/flows/play-move.yaml
appId: com.wordstockt.app
---
- launchApp
- runFlow: login.yaml
- tapOn: "Game with Jessica"
- assertVisible: "Your turn"
# Select tile from rack
- tapOn:
    id: "rack-tile-0"
# Place on board center
- tapOn:
    id: "cell-7-7"
# Select another tile
- tapOn:
    id: "rack-tile-1"
- tapOn:
    id: "cell-8-7"
# Submit move
- tapOn: "Play"
- assertVisible: "Scored"
```

```yaml
# e2e/flows/chat.yaml
appId: com.wordstockt.app
---
- launchApp
- runFlow: login.yaml
- tapOn: "Game with Jessica"
- tapOn: "Chat"
- tapOn: "Message input"
- inputText: "Good game!"
- tapOn: "Send"
- assertVisible: "Good game!"
```

#### Running Tests

```bash
# Run single flow
maestro test e2e/flows/login.yaml

# Run all flows
maestro test e2e/flows/

# Run with video recording
maestro test e2e/flows/ --record
```

### Detox (Alternative)

For more complex E2E testing with better CI integration.

```javascript
// e2e/login.test.js
describe('Login Flow', () => {
  beforeAll(async () => {
    await device.launchApp();
  });

  beforeEach(async () => {
    await device.reloadReactNative();
  });

  it('should login successfully', async () => {
    await element(by.id('email-input')).typeText('test@example.com');
    await element(by.id('password-input')).typeText('password');
    await element(by.id('login-button')).tap();

    await expect(element(by.text('Your Games'))).toBeVisible();
  });

  it('should show error for invalid credentials', async () => {
    await element(by.id('email-input')).typeText('wrong@example.com');
    await element(by.id('password-input')).typeText('wrongpassword');
    await element(by.id('login-button')).tap();

    await expect(element(by.text('Invalid credentials'))).toBeVisible();
  });
});
```

---

## Test Data & Factories

### Laravel Factories

```php
<?php

namespace Database\Factories;

use App\Domain\Game\Enums\GameStatus;use App\Domain\Game\Models\Game;use App\Domain\User\Models\User;use Illuminate\Database\Eloquent\Factories\Factory;

class GameFactory extends Factory
{
    protected $model = Game::class;

    public function definition(): array
    {
        return [
            'language' => $this->faker->randomElement(['nl', 'en']),
            'status' => GameStatus::Pending,
            'board' => $this->emptyBoard(),
            'board_template' => $this->standardTemplate(),
            'tile_bag' => $this->fullTileBag(),
            'current_turn_user_id' => null,
        ];
    }

    public function active(): self
    {
        return $this->state(fn() => [
            'status' => GameStatus::Active,
        ]);
    }

    public function finished(): self
    {
        return $this->state(fn() => [
            'status' => GameStatus::Finished,
        ]);
    }

    public function withPlayers(User $player1, User $player2): self
    {
        return $this->afterCreating(function (Game $game) use ($player1, $player2) {
            $game->players()->create([
                'user_id' => $player1->id,
                'rack' => $this->drawTiles($game, 7),
                'score' => 0,
                'is_current_turn' => true,
            ]);

            $game->players()->create([
                'user_id' => $player2->id,
                'rack' => $this->drawTiles($game, 7),
                'score' => 0,
                'is_current_turn' => false,
            ]);

            $game->update([
                'status' => GameStatus::Active,
                'current_turn_user_id' => $player1->id,
            ]);
        });
    }

    private function emptyBoard(): array
    {
        return array_fill(0, 15, array_fill(0, 15, null));
    }

    private function standardTemplate(): array
    {
        // Return standard board template
    }

    private function fullTileBag(): array
    {
        // Return full tile bag for language
    }

    private function drawTiles(Game $game, int $count): array
    {
        // Draw tiles from bag
    }
}
```

### Running Tests

```bash
# Laravel
php artisan test                    # Run all tests
php artisan test --filter=GameTest  # Run specific test
php artisan test --coverage         # With coverage report

# React Native
npm test                            # Run all tests
npm test -- --watch                 # Watch mode
npm test -- --coverage              # With coverage
npm test -- GameBoard               # Run specific test

# E2E
maestro test e2e/flows/             # Run all E2E tests
```

---

## CI/CD Integration

### GitHub Actions Example

```yaml
# .github/workflows/test.yml
name: Tests

on: [push, pull_request]

jobs:
  backend:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
      - run: composer install
      - run: php artisan test --coverage

  frontend:
    runs-on: ubuntu-latest
    defaults:
      run:
        working-directory: mobile
    steps:
      - uses: actions/checkout@v4
      - uses: actions/setup-node@v4
        with:
          node-version: '18'
      - run: npm ci
      - run: npm test -- --coverage
```
