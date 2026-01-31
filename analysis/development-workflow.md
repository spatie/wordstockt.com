# WordStockt Development Workflow

Guide for adding features, debugging issues, and maintaining code quality.

---

## Table of Contents

1. [Adding a New Feature](#adding-a-new-feature)
2. [Debugging Guide](#debugging-guide)
3. [Common Issues & Solutions](#common-issues--solutions)
4. [Code Style & Conventions](#code-style--conventions)

---

## Adding a New Feature

### Backend Checklist (Laravel)

Use this checklist when adding a new backend feature:

```
□ 1. Migration       - Create database schema changes
□ 2. Model           - Create/update Eloquent model
□ 3. DTO             - Create data transfer objects (if needed)
□ 4. Service         - Implement business logic
□ 5. Rule Classes    - Add validation rules (if game-related)
□ 6. Controller      - Create API endpoint
□ 7. Form Request    - Add request validation
□ 8. Route           - Register API route
□ 9. Event           - Create broadcast event (if real-time)
□ 10. Tests          - Write unit and feature tests
```

#### Example: Adding "Rematch" Feature

**1. Migration**
```bash
php artisan make:migration add_rematch_to_games_table
```

```php
// database/migrations/xxxx_add_rematch_to_games_table.php
public function up(): void
{
    Schema::table('games', function (Blueprint $table) {
        $table->foreignId('rematch_of_game_id')->nullable()->constrained('games');
    });
}
```

**2. Model**
```php
// app/Models/Game.php
public function originalGame(): BelongsTo
{
    return $this->belongsTo(Game::class, 'rematch_of_game_id');
}

public function rematch(): HasOne
{
    return $this->hasOne(Game::class, 'rematch_of_game_id');
}
```

**3. Service**
```php
// app/Services/GameService.php
public function createRematch(Game $originalGame, User $requestingUser): Game
{
    if ($originalGame->status !== GameStatus::Finished) {
        throw new GameException('Can only rematch finished games');
    }

    $opponent = $originalGame->players
        ->where('user_id', '!=', $requestingUser->id)
        ->first()?->user;

    $rematch = $this->createGame(
        creator: $requestingUser,
        language: $originalGame->language,
        opponent: $opponent
    );

    $rematch->update(['rematch_of_game_id' => $originalGame->id]);

    return $rematch;
}
```

**4. Controller**
```php
// app/Http/Controllers/Api/GameController.php
public function rematch(Game $game, Request $request): JsonResponse
{
    $rematch = $this->gameService->createRematch($game, $request->user());

    return response()->json([
        'game' => new GameResource($rematch),
    ], 201);
}
```

**5. Route**
```php
// routes/api.php
Route::post('/games/{game}/rematch', [GameController::class, 'rematch']);
```

**6. Tests**
```php
// tests/Feature/Game/RematchTest.php
/** @test */
public function player_can_request_rematch_of_finished_game(): void
{
    $game = Game::factory()->finished()->create();

    $response = $this->actingAs($game->players->first()->user)
        ->postJson("/api/games/{$game->id}/rematch");

    $response->assertCreated()
        ->assertJsonPath('game.rematch_of_game_id', $game->id);
}
```

---

### Frontend Checklist (React Native)

Use this checklist when adding a new frontend feature:

```
□ 1. Types           - Define TypeScript interfaces
□ 2. Schema          - Create Zod validation schema
□ 3. API Hook        - Create TanStack Query hook
□ 4. Store           - Add Zustand state (if needed)
□ 5. Component       - Build UI components
□ 6. Screen          - Create/update screen
□ 7. Navigation      - Add route (if new screen)
□ 8. Tests           - Write component and hook tests
```

#### Example: Adding "Rematch" Button

**1. Types**
```typescript
// src/types/game.ts
export interface Game {
  // ... existing fields
  rematchOfGameId: number | null;
}
```

**2. Schema**
```typescript
// src/schemas/game.schema.ts
export const GameSchema = z.object({
  // ... existing fields
  rematch_of_game_id: z.number().nullable(),
});
```

**3. API Hook**
```typescript
// src/api/queries/useGame.ts
export function useRequestRematch() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (gameId: number): Promise<Game> => {
      const { data } = await apiClient.post(`/games/${gameId}/rematch`);
      return transformGame(GameSchema.parse(data.game));
    },
    onSuccess: (newGame) => {
      // Add new game to cache
      queryClient.setQueryData(gameKeys.detail(newGame.id), newGame);
      // Invalidate game list
      queryClient.invalidateQueries({ queryKey: gameKeys.lists() });
    },
  });
}
```

**4. Component**
```typescript
// src/components/game/RematchButton.tsx
import React from 'react';
import { Button } from 'react-native-paper';
import { useRequestRematch } from '../../api/queries/useGame';
import { useNavigation } from '@react-navigation/native';

interface RematchButtonProps {
  gameId: number;
  disabled?: boolean;
}

export function RematchButton({ gameId, disabled }: RematchButtonProps) {
  const navigation = useNavigation();
  const rematch = useRequestRematch();

  const handleRematch = async () => {
    const newGame = await rematch.mutateAsync(gameId);
    navigation.navigate('Game', { gameId: newGame.id });
  };

  return (
    <Button
      mode="contained"
      onPress={handleRematch}
      loading={rematch.isPending}
      disabled={disabled || rematch.isPending}
    >
      Rematch
    </Button>
  );
}
```

**5. Add to Screen**
```typescript
// src/screens/GameScreen.tsx
{game.status === 'finished' && (
  <RematchButton gameId={game.id} />
)}
```

---

### Full-Stack Feature Template

For features spanning backend and frontend:

```markdown
## Feature: [Feature Name]

### Requirements
- [ ] Requirement 1
- [ ] Requirement 2

### Backend Tasks
- [ ] Migration: `php artisan make:migration ...`
- [ ] Model updates
- [ ] Service method
- [ ] Controller endpoint
- [ ] Route registration
- [ ] Broadcast event (if real-time)
- [ ] Tests

### Frontend Tasks
- [ ] Types & schemas
- [ ] API hook
- [ ] UI component
- [ ] Screen integration
- [ ] Tests

### API Contract
```
POST /api/games/{id}/feature
Request: { field1: string, field2: number }
Response: { game: Game, result: ... }
```
```

---

## Debugging Guide

### Laravel Debugging

#### Laravel Telescope

Telescope provides insight into requests, exceptions, logs, and more.

```bash
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate
```

Access at: `http://wordstockt.com.test/telescope`

**Useful Telescope features:**
- **Requests**: See all API requests, payloads, and responses
- **Exceptions**: View full stack traces
- **Queries**: See all database queries with timing
- **Events**: Monitor broadcast events

#### Logging

```php
// Structured logging
use Illuminate\Support\Facades\Log;

Log::info('Move played', [
    'game_id' => $game->id,
    'user_id' => $user->id,
    'tiles' => $tiles,
    'score' => $score,
]);

// Log to specific channel
Log::channel('game')->info('...');
```

View logs:
```bash
tail -f storage/logs/laravel.log
```

#### Tinker (REPL)

```bash
php artisan tinker
```

```php
// Query games
Game::where('status', 'active')->with('players')->get();

// Test service methods
app(GameService::class)->createGame(User::first(), 'nl');

// Check model relationships
Game::find(1)->players->map->user->pluck('username');
```

#### Debug Queries

```php
// Enable query logging
DB::enableQueryLog();

// ... your code ...

// Dump queries
dd(DB::getQueryLog());
```

#### Xdebug

For step-through debugging, configure Xdebug:

```ini
; php.ini
xdebug.mode=debug
xdebug.start_with_request=yes
xdebug.client_port=9003
```

---

### React Native Debugging

#### Flipper

Official debugging tool for React Native.

1. Install Flipper: https://fbflipper.com
2. Plugins to install:
   - React DevTools
   - Network
   - Databases (AsyncStorage)
   - Logs

#### React DevTools

Inspect component tree, props, and state:

```bash
# Standalone
npx react-devtools
```

Or use Flipper's integrated React DevTools.

#### Console Logging

```typescript
// Basic logging
console.log('Game state:', game);

// Group related logs
console.group('Move submission');
console.log('Tiles:', tiles);
console.log('Game ID:', gameId);
console.groupEnd();

// Conditional debug logging
if (__DEV__) {
  console.log('Debug info:', data);
}
```

#### Network Debugging

Use Flipper's Network plugin or:

```typescript
// Add axios interceptor for logging
apiClient.interceptors.request.use((config) => {
  console.log(`[API] ${config.method?.toUpperCase()} ${config.url}`, config.data);
  return config;
});

apiClient.interceptors.response.use(
  (response) => {
    console.log(`[API] Response:`, response.data);
    return response;
  },
  (error) => {
    console.error(`[API] Error:`, error.response?.data);
    return Promise.reject(error);
  }
);
```

#### React Query DevTools

```typescript
// App.tsx (development only)
import { ReactQueryDevtools } from '@tanstack/react-query-devtools';

export default function App() {
  return (
    <QueryClientProvider client={queryClient}>
      <App />
      {__DEV__ && <ReactQueryDevtools />}
    </QueryClientProvider>
  );
}
```

#### Debug WebSocket

```typescript
// Enable verbose WebSocket logging
class WebSocketService {
  private debug = __DEV__;

  private log(...args: unknown[]) {
    if (this.debug) {
      console.log('[WS]', ...args);
    }
  }

  // Use throughout service
  this.log('Connected, socket ID:', this.socketId);
  this.log('Received event:', event, data);
}
```

---

## Common Issues & Solutions

### CORS Errors

**Symptom**: Browser/app shows "CORS policy" error.

**Solution**:

```php
// config/cors.php
return [
    'paths' => ['api/*', 'broadcasting/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [
        'http://localhost:3000',
        'http://localhost:8081',
        'http://wordstockt.com.test',
    ],
    'allowed_headers' => ['*'],
    'supports_credentials' => true,
];
```

For React Native, ensure you're not running into a different issue (network not CORS).

---

### Token Expiration / 401 Errors

**Symptom**: API calls return 401 Unauthorized.

**Solutions**:

1. **Check token exists**:
```typescript
const token = useAuthStore.getState().token;
console.log('Token:', token ? 'exists' : 'missing');
```

2. **Check token in request**:
```typescript
apiClient.interceptors.request.use((config) => {
  console.log('Auth header:', config.headers.Authorization);
  return config;
});
```

3. **Handle 401 globally**:
```typescript
apiClient.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      useAuthStore.getState().logout();
      // Navigate to login
    }
    return Promise.reject(error);
  }
);
```

---

### WebSocket Connection Failures

**Symptom**: WebSocket won't connect or disconnects frequently.

**Debug steps**:

1. **Check Reverb is running**:
```bash
php artisan reverb:start --debug
```

2. **Check WebSocket URL**:
```typescript
console.log('Connecting to:', WS_HOST);
// Should match REVERB_HOST:REVERB_PORT
```

3. **Check auth endpoint**:
```bash
curl -X POST http://wordstockt.com.test/broadcasting/auth \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d "socket_id=123.456&channel_name=private-game.1"
```

4. **Network issues on mobile**:
   - Use IP address instead of localhost for physical devices
   - Check firewall allows port 8080

---

### Type Mismatches Between Backend/Frontend

**Symptom**: Runtime errors, undefined properties, Zod validation failures.

**Prevention**:

1. **Use Zod for all API responses**:
```typescript
const validated = GameSchema.safeParse(data);
if (!validated.success) {
  console.error('API response validation failed:', validated.error);
  throw new Error('Invalid API response');
}
```

2. **Transform snake_case consistently**:
```typescript
// Always transform in one place
export function transformGame(data: GameResponse): Game {
  return {
    id: data.id,
    currentTurnUserId: data.current_turn_user_id, // snake_case → camelCase
    // ...
  };
}
```

3. **Log response shape during development**:
```typescript
console.log('Raw API response:', JSON.stringify(data, null, 2));
```

---

### "Not Your Turn" Errors

**Symptom**: Move submission rejected with "Not your turn".

**Debug**:

```typescript
// Check turn state
console.log('Current turn user:', game.currentTurnUserId);
console.log('My user ID:', userId);
console.log('Is my turn:', game.currentTurnUserId === userId);
```

**Common causes**:
- Stale game state (cache not updated after opponent's move)
- WebSocket event not processed

**Fix**: Force refetch
```typescript
queryClient.invalidateQueries({ queryKey: gameKeys.detail(gameId) });
```

---

### Tiles Not Appearing in Rack

**Symptom**: Rack shows empty or wrong tiles.

**Debug**:

```typescript
console.log('Raw rack from API:', game.my_rack);
console.log('Transformed rack:', game.myRack);
```

**Common causes**:
- Transformation not applied
- API returning opponent's rack visibility (should be hidden)

---

### Move Validation Failures

**Symptom**: Valid-looking moves rejected.

**Debug on backend**:

```php
// In BoardService or controller
Log::debug('Move validation', [
    'tiles' => $tiles,
    'board' => $game->board,
    'errors' => $validationResult['errors'] ?? [],
]);
```

**Common validation issues**:
- First move doesn't cover center (7,7)
- Gap between tiles
- Not connected to existing tiles
- Word not in dictionary

---

## Code Style & Conventions

### PHP (Laravel)

Follow PSR-12 and Laravel conventions.

**Naming**:
```php
// Classes: PascalCase
class GameService {}
class MovePlayed {}

// Methods: camelCase
public function createGame() {}
public function validatePlacement() {}

// Variables: camelCase
$currentPlayer = $game->currentPlayer;
$tileCount = count($tiles);

// Constants: UPPER_SNAKE_CASE
const MAX_RACK_SIZE = 7;
const BOARD_SIZE = 15;
```

**File organization**:

```php
<?php

namespace App\Services;

use App\Domain\Game\Models\Game;use App\Domain\Game\Support\Board;use App\Support\ScoringService;          // 1. PHP/Framework imports

// 2. App imports

class GameService             // 3. Class definition
{
    public function __construct(
        private Board $boardService,
        private ScoringService $scoringService,
    ) {}

    // Public methods first
    public function createGame(): Game {}

    // Private methods last
    private function validateMove(): bool {}
}
```

**Type hints**:
```php
// Always use type hints
public function playMove(Game $game, User $player, array $tiles): Move
{
    // ...
}

// Use union types when needed
public function findGame(int|string $id): ?Game
{
    // ...
}
```

---

### TypeScript (React Native)

**Naming**:
```typescript
// Components: PascalCase
function GameBoard() {}
function TileRack() {}

// Hooks: camelCase with "use" prefix
function useGame() {}
function useWebSocket() {}

// Types/Interfaces: PascalCase
interface Game {}
type GameStatus = 'pending' | 'active' | 'finished';

// Constants: UPPER_SNAKE_CASE
const BOARD_SIZE = 15;
const API_BASE_URL = '...';

// Variables/functions: camelCase
const currentPlayer = game.players[0];
function calculateScore() {}
```

**File organization**:
```typescript
// 1. React imports
import React, { useState, useCallback } from 'react';

// 2. External library imports
import { View, StyleSheet } from 'react-native';
import { Button } from 'react-native-paper';

// 3. Internal imports (absolute paths)
import { useGame } from '@/api/queries/useGame';
import type { Game } from '@/types';

// 4. Component definition
interface Props {
  gameId: number;
}

export function GameScreen({ gameId }: Props) {
  // Hooks at top
  const { data: game } = useGame(gameId);
  const [error, setError] = useState<string | null>(null);

  // Handlers
  const handlePress = useCallback(() => {
    // ...
  }, []);

  // Render
  return (
    <View style={styles.container}>
      {/* ... */}
    </View>
  );
}

// 5. Styles at bottom
const styles = StyleSheet.create({
  container: {
    flex: 1,
  },
});
```

**Props interface pattern**:
```typescript
// Define props interface above component
interface GameBoardProps {
  game: Game;
  onCellPress: (x: number, y: number) => void;
  isMyTurn: boolean;
}

export function GameBoard({ game, onCellPress, isMyTurn }: GameBoardProps) {
  // ...
}
```

**Avoid `any`**:
```typescript
// Bad
function handleData(data: any) {}

// Good
function handleData(data: unknown) {
  if (isGame(data)) {
    // Now typed as Game
  }
}

// Or use generics
function handleResponse<T>(data: T): T {
  return data;
}
```

---

### Git Conventions

**Branch naming**:
```
feature/add-rematch-button
fix/websocket-reconnection
refactor/game-service
docs/testing-guide
```

**Commit messages**:
```
feat: add rematch functionality

- Add rematch endpoint to GameController
- Create RematchButton component
- Update game types for rematch_of_game_id

Closes #123
```

Prefixes:
- `feat:` - New feature
- `fix:` - Bug fix
- `refactor:` - Code refactoring
- `docs:` - Documentation
- `test:` - Adding tests
- `chore:` - Maintenance tasks

**Pull request template**:
```markdown
## Summary
Brief description of changes.

## Changes
- Change 1
- Change 2

## Testing
- [ ] Unit tests added/updated
- [ ] Manual testing completed

## Screenshots (if UI changes)
```
