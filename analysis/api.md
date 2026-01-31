# WordStockt API Reference

Base URL: `http://wordstockt.com.test/api`

## Authentication

All authenticated endpoints require the header:
```
Authorization: Bearer {token}
```

### POST /auth/register
Create a new user account.

**Request:**
```json
{
  "username": "player1",
  "email": "player1@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

**Response (201):**
```json
{
  "user": {
    "id": 1,
    "username": "player1",
    "email": "player1@example.com"
  },
  "token": "1|abc123..."
}
```

### POST /auth/login
Authenticate and receive token.

**Request:**
```json
{
  "email": "player1@example.com",
  "password": "password123"
}
```

**Response (200):**
```json
{
  "user": {
    "id": 1,
    "username": "player1",
    "email": "player1@example.com",
    "games_played": 10,
    "games_won": 6
  },
  "token": "2|xyz789..."
}
```

### POST /auth/logout
Invalidate current token.

**Response (200):**
```json
{
  "message": "Logged out successfully"
}
```

### GET /auth/user
Get current authenticated user.

**Response (200):**
```json
{
  "id": 1,
  "username": "player1",
  "email": "player1@example.com",
  "games_played": 10,
  "games_won": 6
}
```

---

## Games

### GET /games
List current user's games.

**Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "status": "active",
      "language": "nl",
      "current_turn_user_id": 1,
      "players": [
        {"id": 1, "username": "player1", "score": 45},
        {"id": 2, "username": "player2", "score": 32}
      ],
      "tiles_remaining": 76,
      "updated_at": "2024-01-15T10:30:00Z"
    }
  ]
}
```

### POST /games
Create a new game.

**Request:**
```json
{
  "language": "nl",
  "opponent_id": 2  // Optional - creates pending game if omitted
}
```

**Response (201):**
```json
{
  "game": {
    "id": 1,
    "status": "active",
    "language": "nl",
    "board": [[null, ...], ...],
    "board_template": [["TW", null, ...], ...],
    "players": [...],
    "my_rack": [
      {"letter": "A", "points": 1, "is_blank": false},
      ...
    ],
    "tiles_remaining": 88,
    "current_turn_user_id": 1
  }
}
```

### GET /games/pending
List games waiting for opponent.

**Response (200):**
```json
{
  "data": [
    {
      "id": 3,
      "creator": {"id": 5, "username": "waiting_player"},
      "language": "nl",
      "created_at": "2024-01-15T09:00:00Z"
    }
  ]
}
```

### GET /games/{id}
Get full game state.

**Response (200):**
```json
{
  "id": 1,
  "language": "nl",
  "status": "active",
  "board": [
    [null, null, null, ...],
    [null, null, {"letter": "W", "points": 5}, ...],
    ...
  ],
  "board_template": [
    ["TW", null, null, "DL", ...],
    ...
  ],
  "players": [
    {
      "id": 1,
      "username": "player1",
      "score": 45,
      "rack_count": 7,
      "is_current_turn": true
    },
    {
      "id": 2,
      "username": "player2",
      "score": 32,
      "rack_count": 6,
      "is_current_turn": false
    }
  ],
  "my_rack": [
    {"letter": "A", "points": 1, "is_blank": false},
    {"letter": "E", "points": 1, "is_blank": false},
    ...
  ],
  "tiles_remaining": 76,
  "current_turn_user_id": 1,
  "winner_id": null,
  "last_move": {
    "id": 15,
    "user_id": 2,
    "type": "play",
    "words": ["WORD"],
    "score": 12
  }
}
```

### POST /games/{id}/join
Join a pending game.

**Response (200):**
```json
{
  "game": { ... }
}
```

### POST /games/{id}/moves
Submit a move (place tiles).

**Request:**
```json
{
  "tiles": [
    {"letter": "W", "points": 5, "x": 7, "y": 7, "is_blank": false},
    {"letter": "O", "points": 1, "x": 8, "y": 7, "is_blank": false},
    {"letter": "R", "points": 2, "x": 9, "y": 7, "is_blank": false},
    {"letter": "D", "points": 2, "x": 10, "y": 7, "is_blank": false}
  ]
}
```

**Response (200):**
```json
{
  "game": { ... },
  "move": {
    "id": 16,
    "type": "play",
    "words": ["WORD"],
    "score": 20
  }
}
```

**Error Response (422):**
```json
{
  "message": "The following words are not valid: 'XYZ'."
}
```

### POST /games/{id}/pass
Pass turn.

**Response (200):**
```json
{
  "game": { ... },
  "move": {
    "id": 17,
    "type": "pass",
    "score": 0
  }
}
```

### POST /games/{id}/swap
Swap tiles with bag.

**Request:**
```json
{
  "tiles": [
    {"letter": "Q", "points": 10},
    {"letter": "X", "points": 8}
  ]
}
```

**Response (200):**
```json
{
  "game": { ... },
  "move": {
    "id": 18,
    "type": "swap",
    "score": 0
  }
}
```

### POST /games/{id}/resign
Resign from game.

**Response (200):**
```json
{
  "game": {
    "status": "finished",
    "winner_id": 2,
    ...
  },
  "move": {
    "id": 19,
    "type": "resign",
    "score": 0
  }
}
```

---

## Messages

### GET /games/{id}/messages
Get chat messages for a game.

**Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "user": {"id": 1, "username": "player1"},
      "content": "Good game!",
      "created_at": "2024-01-15T10:35:00Z"
    }
  ]
}
```

### POST /games/{id}/messages
Send a chat message.

**Request:**
```json
{
  "content": "Nice move!"
}
```

**Response (201):**
```json
{
  "id": 2,
  "user": {"id": 2, "username": "player2"},
  "content": "Nice move!",
  "created_at": "2024-01-15T10:36:00Z"
}
```

---

## Users

### GET /users/search?query={term}
Search users by username.

**Response (200):**
```json
{
  "data": [
    {"id": 3, "username": "player3"},
    {"id": 4, "username": "player4"}
  ]
}
```

### GET /users/{id}
Get user profile.

**Response (200):**
```json
{
  "id": 3,
  "username": "player3",
  "games_played": 25,
  "games_won": 15
}
```

### GET /leaderboard
Get top 50 players.

**Response (200):**
```json
{
  "data": [
    {"id": 1, "username": "champion", "games_won": 100, "games_played": 120},
    {"id": 2, "username": "runner_up", "games_won": 85, "games_played": 110},
    ...
  ]
}
```

---

## WebSocket Events

Connect to: `ws://wordstockt.com.test:8080/app/{key}`

### Channels

| Channel | Access |
|---------|--------|
| `private-game.{id}` | Game participants only |
| `private-user.{id}` | User only |

### Events

#### move.played
Broadcast when a move is made.
```json
{
  "game": { ... },
  "move": { ... },
  "user": { ... }
}
```

#### message.sent
Broadcast when chat message sent.
```json
{
  "message": { ... },
  "user": { ... }
}
```

#### game.invitation
Broadcast when invited to a game.
```json
{
  "game": { ... },
  "inviter": { ... }
}
```

---

## Error Codes

| Code | Description |
|------|-------------|
| 401 | Unauthenticated |
| 403 | Not authorized (not your game) |
| 404 | Game/User not found |
| 422 | Validation error (invalid move, bad input) |
| 500 | Server error |

---

## Frontend Integration

Complete React Native examples showing how to consume each endpoint with TanStack Query and Zod validation.

### Authentication Hooks

```typescript
// src/api/queries/useAuth.ts
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { z } from 'zod';
import { apiClient, getApiError } from '../client';
import { useAuthStore } from '../../stores/authStore';

// Schemas
const UserSchema = z.object({
  id: z.number(),
  username: z.string(),
  email: z.string().email(),
  games_played: z.number().optional(),
  games_won: z.number().optional(),
});

const AuthResponseSchema = z.object({
  user: UserSchema,
  token: z.string(),
});

// Types
type User = z.infer<typeof UserSchema>;
type AuthResponse = z.infer<typeof AuthResponseSchema>;

// Transform snake_case to camelCase
function transformUser(data: z.infer<typeof UserSchema>) {
  return {
    id: data.id,
    username: data.username,
    email: data.email,
    gamesPlayed: data.games_played ?? 0,
    gamesWon: data.games_won ?? 0,
  };
}

// Login mutation
export function useLogin() {
  const setAuth = useAuthStore((s) => s.setAuth);

  return useMutation({
    mutationFn: async (credentials: { email: string; password: string }) => {
      const { data } = await apiClient.post('/auth/login', credentials);
      const validated = AuthResponseSchema.parse(data);
      return {
        user: transformUser(validated.user),
        token: validated.token,
      };
    },
    onSuccess: ({ user, token }) => {
      setAuth(user, token);
    },
    onError: (error) => {
      const apiError = getApiError(error);
      // Return structured error for UI
      throw new Error(apiError.message);
    },
  });
}

// Register mutation
export function useRegister() {
  const setAuth = useAuthStore((s) => s.setAuth);

  return useMutation({
    mutationFn: async (data: {
      username: string;
      email: string;
      password: string;
      password_confirmation: string;
    }) => {
      const { data: response } = await apiClient.post('/auth/register', data);
      const validated = AuthResponseSchema.parse(response);
      return {
        user: transformUser(validated.user),
        token: validated.token,
      };
    },
    onSuccess: ({ user, token }) => {
      setAuth(user, token);
    },
  });
}

// Logout mutation
export function useLogout() {
  const logout = useAuthStore((s) => s.logout);
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async () => {
      await apiClient.post('/auth/logout');
    },
    onSuccess: () => {
      logout();
      queryClient.clear(); // Clear all cached data
    },
  });
}

// Get current user
export function useCurrentUser() {
  const token = useAuthStore((s) => s.token);

  return useQuery({
    queryKey: ['auth', 'user'],
    queryFn: async () => {
      const { data } = await apiClient.get('/auth/user');
      const validated = UserSchema.parse(data);
      return transformUser(validated);
    },
    enabled: !!token, // Only fetch if authenticated
  });
}
```

### Game Hooks

```typescript
// src/api/queries/useGame.ts
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { apiClient, getApiError } from '../client';
import { GameSchema, transformGame } from '../../schemas/game.schema';
import type { Game, PlacedTile } from '../../types';

// Query keys factory
export const gameKeys = {
  all: ['games'] as const,
  lists: () => [...gameKeys.all, 'list'] as const,
  list: (filter: string) => [...gameKeys.lists(), filter] as const,
  pending: () => [...gameKeys.all, 'pending'] as const,
  details: () => [...gameKeys.all, 'detail'] as const,
  detail: (id: number) => [...gameKeys.details(), id] as const,
};

// Fetch all user's games
export function useGames() {
  return useQuery({
    queryKey: gameKeys.lists(),
    queryFn: async (): Promise<Game[]> => {
      const { data } = await apiClient.get('/games');
      return data.data.map((g: unknown) => {
        const validated = GameSchema.parse(g);
        return transformGame(validated);
      });
    },
    staleTime: 30_000,
  });
}

// Fetch single game with full state
export function useGame(gameId: number) {
  return useQuery({
    queryKey: gameKeys.detail(gameId),
    queryFn: async (): Promise<Game> => {
      const { data } = await apiClient.get(`/games/${gameId}`);
      const validated = GameSchema.parse(data);
      return transformGame(validated);
    },
    staleTime: 10_000,
    refetchInterval: (query) => {
      // Poll more frequently when it's user's turn
      const game = query.state.data;
      const userId = useAuthStore.getState().user?.id;
      if (game?.currentTurnUserId === userId) {
        return false; // Don't poll when it's my turn (rely on WebSocket)
      }
      return 30_000; // Poll every 30s when waiting for opponent
    },
  });
}

// Fetch pending games (joinable)
export function usePendingGames() {
  return useQuery({
    queryKey: gameKeys.pending(),
    queryFn: async () => {
      const { data } = await apiClient.get('/games/pending');
      return data.data;
    },
  });
}

// Create new game
export function useCreateGame() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (params: { language: string; opponentId?: number }) => {
      const { data } = await apiClient.post('/games', {
        language: params.language,
        opponent_id: params.opponentId,
      });
      const validated = GameSchema.parse(data.game);
      return transformGame(validated);
    },
    onSuccess: (game) => {
      queryClient.setQueryData(gameKeys.detail(game.id), game);
      queryClient.invalidateQueries({ queryKey: gameKeys.lists() });
    },
  });
}

// Join pending game
export function useJoinGame() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (gameId: number) => {
      const { data } = await apiClient.post(`/games/${gameId}/join`);
      const validated = GameSchema.parse(data.game);
      return transformGame(validated);
    },
    onSuccess: (game) => {
      queryClient.setQueryData(gameKeys.detail(game.id), game);
      queryClient.invalidateQueries({ queryKey: gameKeys.pending() });
      queryClient.invalidateQueries({ queryKey: gameKeys.lists() });
    },
  });
}

// Submit move with optimistic update
export function useSubmitMove() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async ({ gameId, tiles }: { gameId: number; tiles: PlacedTile[] }) => {
      const { data } = await apiClient.post(`/games/${gameId}/moves`, {
        tiles: tiles.map((t) => ({
          letter: t.letter,
          points: t.points,
          x: t.x,
          y: t.y,
          is_blank: t.isBlank,
        })),
      });
      return {
        game: transformGame(GameSchema.parse(data.game)),
        move: data.move as { id: number; score: number; words: string[] },
      };
    },
    // Optimistic update: show tiles on board immediately
    onMutate: async ({ gameId, tiles }) => {
      await queryClient.cancelQueries({ queryKey: gameKeys.detail(gameId) });

      const previousGame = queryClient.getQueryData<Game>(gameKeys.detail(gameId));

      if (previousGame) {
        const newBoard = [...previousGame.board.map((row) => [...row])];
        tiles.forEach((tile) => {
          newBoard[tile.y][tile.x] = {
            letter: tile.letter,
            points: tile.points,
            isBlank: tile.isBlank,
            x: tile.x,
            y: tile.y,
          };
        });

        queryClient.setQueryData<Game>(gameKeys.detail(gameId), {
          ...previousGame,
          board: newBoard,
        });
      }

      return { previousGame };
    },
    onError: (err, { gameId }, context) => {
      // Rollback on error
      if (context?.previousGame) {
        queryClient.setQueryData(gameKeys.detail(gameId), context.previousGame);
      }
    },
    onSuccess: ({ game }, { gameId }) => {
      // Replace with server response
      queryClient.setQueryData(gameKeys.detail(gameId), game);
      queryClient.invalidateQueries({ queryKey: gameKeys.lists() });
    },
  });
}

// Pass turn
export function usePassTurn() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (gameId: number) => {
      const { data } = await apiClient.post(`/games/${gameId}/pass`);
      return transformGame(GameSchema.parse(data.game));
    },
    onSuccess: (game) => {
      queryClient.setQueryData(gameKeys.detail(game.id), game);
    },
  });
}

// Swap tiles
export function useSwapTiles() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async ({ gameId, tiles }: { gameId: number; tiles: { letter: string; points: number }[] }) => {
      const { data } = await apiClient.post(`/games/${gameId}/swap`, { tiles });
      return transformGame(GameSchema.parse(data.game));
    },
    onSuccess: (game) => {
      queryClient.setQueryData(gameKeys.detail(game.id), game);
    },
  });
}

// Resign from game
export function useResign() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (gameId: number) => {
      const { data } = await apiClient.post(`/games/${gameId}/resign`);
      return transformGame(GameSchema.parse(data.game));
    },
    onSuccess: (game) => {
      queryClient.setQueryData(gameKeys.detail(game.id), game);
      queryClient.invalidateQueries({ queryKey: gameKeys.lists() });
    },
  });
}
```

### Handling API Errors

```typescript
// src/api/client.ts - Error handling utilities
import axios, { AxiosError } from 'axios';

export interface ApiError {
  message: string;
  status: number;
  errors?: Record<string, string[]>; // Validation errors
}

export function getApiError(error: unknown): ApiError {
  if (axios.isAxiosError(error)) {
    const axiosError = error as AxiosError<{ message: string; errors?: Record<string, string[]> }>;

    return {
      message: axiosError.response?.data?.message ?? 'Network error',
      status: axiosError.response?.status ?? 500,
      errors: axiosError.response?.data?.errors,
    };
  }

  if (error instanceof Error) {
    return { message: error.message, status: 500 };
  }

  return { message: 'Unknown error', status: 500 };
}

// Usage in components
function LoginScreen() {
  const login = useLogin();

  const handleLogin = async () => {
    try {
      await login.mutateAsync({ email, password });
    } catch (error) {
      const apiError = getApiError(error);

      if (apiError.status === 401) {
        setError('Invalid email or password');
      } else if (apiError.status === 422 && apiError.errors) {
        // Show validation errors
        const firstError = Object.values(apiError.errors)[0]?.[0];
        setError(firstError ?? 'Validation failed');
      } else {
        setError(apiError.message);
      }
    }
  };
}
```

### Response Status Handling

```typescript
// Handling different HTTP status codes

// 401 - Unauthenticated (token expired/invalid)
apiClient.interceptors.response.use(
  (response) => response,
  (error: AxiosError) => {
    if (error.response?.status === 401) {
      // Clear auth state and redirect to login
      useAuthStore.getState().logout();
      // Navigation handled by auth state change in RootNavigator
    }
    return Promise.reject(error);
  }
);

// 403 - Forbidden (not authorized for resource)
// Handle in mutation/query error callbacks
const { error } = useGame(gameId);
if (error && getApiError(error).status === 403) {
  // User doesn't have access to this game
  navigation.navigate('Home');
}

// 422 - Validation Error (invalid move, bad input)
const submitMove = useSubmitMove();
try {
  await submitMove.mutateAsync({ gameId, tiles });
} catch (error) {
  const apiError = getApiError(error);
  if (apiError.status === 422) {
    // Show validation message (e.g., "Invalid word: XYZ")
    showSnackbar(apiError.message);
  }
}

// 404 - Not Found
const { error, isError } = useGame(gameId);
if (isError && getApiError(error).status === 404) {
  // Game doesn't exist
  navigation.navigate('Home');
}
```

### Complete Usage Example

```typescript
// src/screens/GameScreen.tsx
import React, { useState, useCallback } from 'react';
import { View, StyleSheet } from 'react-native';
import { Snackbar } from 'react-native-paper';
import { useGame, useSubmitMove, usePassTurn } from '../api/queries/useGame';
import { useGameStore } from '../stores/gameStore';
import { useGameWebSocket } from '../hooks/useWebSocket';
import { getApiError } from '../api/client';
import type { GameScreenProps } from '../navigation/types';

export default function GameScreen({ route }: GameScreenProps) {
  const { gameId } = route.params;
  const [error, setError] = useState<string | null>(null);

  // Server state
  const { data: game, isLoading, isError } = useGame(gameId);
  const submitMove = useSubmitMove();
  const passTurn = usePassTurn();

  // Local state
  const pendingTiles = useGameStore((s) => s.pendingTiles);
  const clearPendingTiles = useGameStore((s) => s.clearPendingTiles);

  // Real-time updates
  useGameWebSocket(gameId);

  const handlePlay = useCallback(async () => {
    if (pendingTiles.length === 0) return;

    try {
      const result = await submitMove.mutateAsync({ gameId, tiles: pendingTiles });
      clearPendingTiles();
      setError(null);
      // Optionally show success: `Scored ${result.move.score} points!`
    } catch (err) {
      const apiError = getApiError(err);
      setError(apiError.message); // e.g., "Invalid word: XYZ"
    }
  }, [gameId, pendingTiles, submitMove, clearPendingTiles]);

  const handlePass = useCallback(async () => {
    try {
      await passTurn.mutateAsync(gameId);
      clearPendingTiles();
    } catch (err) {
      setError(getApiError(err).message);
    }
  }, [gameId, passTurn, clearPendingTiles]);

  if (isLoading) return <LoadingView />;
  if (isError || !game) return <ErrorView message="Failed to load game" />;

  return (
    <View style={styles.container}>
      <GameBoard game={game} />
      <TileRack tiles={game.myRack} />
      <ActionButtons
        onPlay={handlePlay}
        onPass={handlePass}
        isLoading={submitMove.isPending}
      />
      <Snackbar
        visible={error !== null}
        onDismiss={() => setError(null)}
        duration={4000}
      >
        {error}
      </Snackbar>
    </View>
  );
}
```
