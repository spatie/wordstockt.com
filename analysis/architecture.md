# WordStockt Architecture

A multiplayer word game (Wordfeud clone) built with Laravel backend and React Native frontend.

## System Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                     React Native App                             │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────────────┐ │
│  │  Screens │  │  Stores  │  │   API    │  │    Components    │ │
│  │          │  │ (Zustand)│  │(TanStack)│  │ (Board, Tiles)   │ │
│  └────┬─────┘  └────┬─────┘  └────┬─────┘  └────────┬─────────┘ │
└───────┼─────────────┼─────────────┼─────────────────┼───────────┘
        │             │             │                 │
        └─────────────┴──────┬──────┴─────────────────┘
                             │
                    HTTP/WebSocket
                             │
┌────────────────────────────┼────────────────────────────────────┐
│                            │                                     │
│  ┌─────────────────────────┴─────────────────────────────────┐  │
│  │                    Laravel API                             │  │
│  │  ┌─────────────┐  ┌─────────────┐  ┌─────────────────────┐│  │
│  │  │ Controllers │  │   Events    │  │      Services       ││  │
│  │  │ Auth, Game  │  │ MovePlayed  │  │ Game, Board, Tile   ││  │
│  │  │ Message     │  │ MessageSent │  │ Scoring, Dictionary ││  │
│  │  └──────┬──────┘  └──────┬──────┘  └──────────┬──────────┘│  │
│  └─────────┼────────────────┼────────────────────┼───────────┘  │
│            │                │                    │               │
│  ┌─────────┴────────────────┴────────────────────┴───────────┐  │
│  │                        Models                              │  │
│  │    User  │  Game  │  GamePlayer  │  Move  │  Dictionary   │  │
│  └────────────────────────────┬──────────────────────────────┘  │
│                               │                                  │
│  ┌────────────────────────────┴──────────────────────────────┐  │
│  │                      SQLite Database                       │  │
│  └───────────────────────────────────────────────────────────┘  │
│                                                                  │
│                        Laravel Backend                           │
└──────────────────────────────────────────────────────────────────┘
```

## Tech Stack

### Backend (Laravel 12)
- **PHP 8.2+** - Server runtime
- **Laravel Sanctum** - API token authentication
- **Laravel Reverb** - WebSocket server for real-time updates
- **SQLite** - Database (development)

### Frontend (React Native + TypeScript)
- **React Native 0.76+** - Cross-platform framework
- **TypeScript** (strict mode) - Type safety
- **React Native Paper** - Material Design 3 component library
- **TanStack Query** - Data fetching, caching, type-safe API calls
- **Zustand** - Lightweight state management
- **Zod** - Runtime validation & type inference
- **React Navigation** - Type-safe navigation
- **Expo** - Development tooling (optional)

## Data Flow

### Game Move Flow
```
1. User taps tile in rack, then taps board cell (React Native)
2. User presses "Play" button
3. React Native sends POST /api/games/{id}/moves
4. Laravel validates:
   - User's turn
   - Tiles in rack
   - Valid placement (line, no gaps, connected)
   - Words exist in dictionary
5. Laravel calculates score
6. Laravel updates game state
7. Laravel broadcasts MovePlayed event
8. React Native updates UI via WebSocket
```

### Authentication Flow
```
1. User enters credentials (React Native)
2. React Native sends POST /api/auth/login
3. Laravel validates credentials
4. Laravel returns Sanctum token
5. React Native stores token in AsyncStorage
6. React Native includes token in all API requests
```

## Directory Structure

### Backend
```
wordstockt.com/
├── app/
│   ├── Console/Commands/     # Artisan commands
│   │   └── ImportDictionary  # dictionary:import command
│   ├── Enums/
│   │   ├── GameStatus.php    # Pending, Active, Finished
│   │   └── MoveType.php      # Play, Pass, Swap, Resign
│   ├── Events/
│   │   ├── MovePlayed.php    # Broadcast on move
│   │   ├── MessageSent.php   # Broadcast on chat
│   │   └── GameInvitation.php
│   ├── Exceptions/
│   │   ├── GameException.php
│   │   └── InvalidMoveException.php
│   ├── Http/Controllers/Api/
│   │   ├── AuthController.php
│   │   ├── GameController.php
│   │   ├── MessageController.php
│   │   └── UserController.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── Game.php
│   │   ├── GamePlayer.php
│   │   ├── Move.php
│   │   ├── Message.php
│   │   └── Dictionary.php
│   └── Services/
│       ├── GameService.php      # Core game logic
│       ├── BoardService.php     # Board validation
│       ├── TileService.php      # Tile bag management
│       ├── ScoringService.php   # Score calculation
│       └── DictionaryService.php # Word validation
├── database/
│   └── migrations/
└── routes/
    ├── api.php      # API routes
    └── channels.php # WebSocket channels
```

### Frontend (React Native)
```
mobile/
├── src/
│   ├── api/
│   │   ├── client.ts           # Axios instance with interceptors
│   │   ├── queries/            # TanStack Query hooks
│   │   │   ├── useAuth.ts
│   │   │   ├── useGames.ts
│   │   │   └── useGame.ts
│   │   └── types.ts            # API response types
│   ├── components/
│   │   ├── game/
│   │   │   ├── GameBoard.tsx   # 15x15 grid
│   │   │   ├── BoardCell.tsx   # Individual cell
│   │   │   ├── Tile.tsx        # Draggable tile
│   │   │   └── TileRack.tsx    # Player's tiles
│   │   └── ui/                 # Reusable UI components
│   ├── hooks/
│   │   ├── useGameState.ts     # Game logic hook
│   │   └── useWebSocket.ts     # Real-time connection
│   ├── navigation/
│   │   ├── RootNavigator.tsx
│   │   └── types.ts            # Navigation param types
│   ├── screens/
│   │   ├── LoginScreen.tsx
│   │   ├── HomeScreen.tsx
│   │   └── GameScreen.tsx
│   ├── stores/
│   │   ├── authStore.ts        # Zustand auth store
│   │   └── gameStore.ts        # Zustand game store
│   ├── schemas/                # Zod schemas
│   │   ├── game.schema.ts
│   │   └── user.schema.ts
│   ├── types/
│   │   ├── game.ts
│   │   ├── user.ts
│   │   └── tile.ts
│   └── utils/
│       ├── scoring.ts          # Score calculation
│       └── validation.ts       # Move validation
├── App.tsx
├── tsconfig.json               # Strict TypeScript config
└── package.json
```

## Key Design Decisions

### 1. Service-based Architecture (Backend)
All business logic is in service classes, not controllers. This makes the code:
- Testable (services can be unit tested)
- Reusable (services can be called from anywhere)
- Maintainable (single responsibility)

### 2. TypeScript Strict Mode
Frontend uses strict TypeScript for maximum type safety:
```json
{
  "compilerOptions": {
    "strict": true,
    "noImplicitAny": true,
    "strictNullChecks": true,
    "noUncheckedIndexedAccess": true
  }
}
```

### 3. TanStack Query for Data Fetching
Chosen over raw fetch/axios because:
- **Type-safe**: Generic query hooks with inferred types
- **Caching**: Automatic cache invalidation
- **Loading states**: Built-in isLoading, isError, isSuccess
- **Optimistic updates**: For responsive UI during moves

### 4. Zustand for Local State
Lightweight alternative to Redux:
- **Simple API**: No boilerplate
- **TypeScript-first**: Full type inference
- **Selective subscriptions**: Only re-render when needed
- Used for: pending tiles, UI state, auth tokens

### 5. Zod for Runtime Validation
Validates API responses at runtime:
- **Type inference**: `z.infer<typeof schema>` generates types
- **Safe parsing**: Handles malformed API responses gracefully
- **Composable**: Reuse schemas across the app

### 6. SQLite for Development
SQLite chosen for simplicity:
- No database server needed
- Single file database
- Easy to reset/migrate

### 7. WebSocket for Real-time
Laravel Reverb handles real-time updates:
- Instant move notifications
- Chat messages
- Game invitations

## Security & Authentication

### Authentication Flow Diagram

```
┌─────────────┐         ┌─────────────┐         ┌─────────────┐
│   Mobile    │         │   Laravel   │         │  Database   │
│    App      │         │     API     │         │             │
└──────┬──────┘         └──────┬──────┘         └──────┬──────┘
       │                       │                       │
       │ POST /auth/login      │                       │
       │ {email, password}     │                       │
       │──────────────────────►│                       │
       │                       │  Verify credentials   │
       │                       │──────────────────────►│
       │                       │                       │
       │                       │◄──────────────────────│
       │                       │  User record          │
       │                       │                       │
       │                       │  Create token         │
       │                       │──────────────────────►│
       │                       │                       │
       │  {user, token}        │                       │
       │◄──────────────────────│                       │
       │                       │                       │
       │  Store token in       │                       │
       │  AsyncStorage         │                       │
       │                       │                       │
       │ GET /api/games        │                       │
       │ Authorization: Bearer │                       │
       │──────────────────────►│                       │
       │                       │  Validate token       │
       │                       │──────────────────────►│
       │                       │                       │
       │  {games: [...]}       │                       │
       │◄──────────────────────│                       │
```

### Token Lifecycle

**Creation:**
```php
// AuthController.php
public function login(LoginRequest $request): JsonResponse
{
    $user = User::where('email', $request->email)->first();

    if (!$user || !Hash::check($request->password, $user->password)) {
        return response()->json(['message' => 'Invalid credentials'], 401);
    }

    // Create token with abilities (optional scoping)
    $token = $user->createToken('mobile-app')->plainTextToken;

    return response()->json([
        'user' => new UserResource($user),
        'token' => $token,
    ]);
}
```

**Storage (React Native):**
```typescript
// Store in Zustand with AsyncStorage persistence
const useAuthStore = create<AuthState>()(
  persist(
    (set) => ({
      token: null,
      setAuth: (user, token) => set({ user, token }),
      logout: () => set({ user: null, token: null }),
    }),
    {
      name: 'auth-storage',
      storage: createJSONStorage(() => AsyncStorage),
      partialize: (state) => ({ token: state.token }), // Only persist token
    }
  )
);
```

**Usage in Requests:**
```typescript
// Axios interceptor adds token to all requests
apiClient.interceptors.request.use((config) => {
  const token = useAuthStore.getState().token;
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});
```

**Expiration:**
- Sanctum tokens don't expire by default
- Configure expiration in `config/sanctum.php`:
```php
'expiration' => 60 * 24 * 7, // 7 days in minutes
```

**Logout (Token Revocation):**
```php
// Revoke current token
$request->user()->currentAccessToken()->delete();

// Or revoke all tokens
$request->user()->tokens()->delete();
```

### CORS Configuration

```php
// config/cors.php
return [
    'paths' => ['api/*', 'broadcasting/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:3000',        // React Native web
        'http://localhost:8081',        // Metro bundler
        'http://wordstockt.com.test',   // Local development
        // Add production origins here
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,  // Required for Sanctum
];
```

### Input Validation

**Form Requests:**
```php
// app/Http/Requests/PlayMoveRequest.php
class PlayMoveRequest extends FormRequest
{
    public function authorize(): bool
    {
        $game = $this->route('game');
        return $game->current_turn_user_id === $this->user()->id;
    }

    public function rules(): array
    {
        return [
            'tiles' => ['required', 'array', 'min:1', 'max:7'],
            'tiles.*.letter' => ['required', 'string', 'size:1'],
            'tiles.*.points' => ['required', 'integer', 'min:0', 'max:10'],
            'tiles.*.x' => ['required', 'integer', 'min:0', 'max:14'],
            'tiles.*.y' => ['required', 'integer', 'min:0', 'max:14'],
            'tiles.*.is_blank' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'tiles.required' => 'You must place at least one tile.',
            'tiles.*.x.max' => 'Tile position out of bounds.',
        ];
    }
}
```

**Service-level Validation:**
```php
// BoardService.php - Business logic validation
public function validatePlacement(array $tiles, array $board): array
{
    // Check tiles are in a line
    // Check no gaps
    // Check connection to existing tiles
    // Check first move covers center
    // Returns ['valid' => bool, 'error' => string|null]
}
```

### Authorization (Policies)

```php
// app/Policies/GamePolicy.php
class GamePolicy
{
    public function view(User $user, Game $game): bool
    {
        return $game->players()->where('user_id', $user->id)->exists();
    }

    public function play(User $user, Game $game): bool
    {
        return $game->current_turn_user_id === $user->id
            && $game->status === GameStatus::Active;
    }

    public function sendMessage(User $user, Game $game): bool
    {
        return $game->players()->where('user_id', $user->id)->exists();
    }
}

// Usage in controller
public function show(Game $game): JsonResponse
{
    $this->authorize('view', $game);
    return response()->json(new GameResource($game));
}
```

### Rate Limiting

```php
// routes/api.php
Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::get('/games', [GameController::class, 'index']);
    Route::post('/games/{game}/moves', [GameController::class, 'move']);
});

// app/Providers/RouteServiceProvider.php
RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
});

// Custom rate limit for moves (prevent spam)
RateLimiter::for('moves', function (Request $request) {
    return Limit::perMinute(10)->by($request->user()->id);
});
```

### Security Best Practices

| Area | Implementation |
|------|----------------|
| **Passwords** | Hashed with bcrypt (Laravel default) |
| **SQL Injection** | Eloquent ORM with parameterized queries |
| **XSS** | JSON API (no HTML rendering) |
| **CSRF** | Not needed for API (token auth) |
| **Mass Assignment** | `$fillable` arrays on models |
| **Sensitive Data** | Never expose password hash, tokens in responses |
| **Error Messages** | Generic messages in production (no stack traces) |

### WebSocket Security

```php
// routes/channels.php
// Private channels require authentication
Broadcast::channel('game.{gameId}', function (User $user, int $gameId) {
    $game = Game::find($gameId);
    return $game && $game->players()->where('user_id', $user->id)->exists();
});

// User's personal channel
Broadcast::channel('user.{userId}', function (User $user, int $userId) {
    return $user->id === $userId;
});
```
