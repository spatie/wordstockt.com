# WordStockt React Native App

## Core Principles

1. **Type Safety**: TypeScript strict mode + Zod runtime validation
2. **Maintainability**: Clear separation of concerns, small focused modules
3. **Readability**: Consistent patterns, self-documenting code

---

## Dependencies

```bash
# Core
npm install react-native-paper react-native-safe-area-context
npm install @react-navigation/native @react-navigation/native-stack
npm install @tanstack/react-query
npm install zustand
npm install zod
npm install axios
npm install @react-native-async-storage/async-storage

# Dev
npm install -D typescript @types/react @types/react-native
```

### React Native Paper Setup

```typescript
// App.tsx
import { PaperProvider, MD3LightTheme } from 'react-native-paper';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { RootNavigator } from './src/navigation/RootNavigator';

const queryClient = new QueryClient();

const theme = {
  ...MD3LightTheme,
  colors: {
    ...MD3LightTheme.colors,
    primary: '#2E7D32',      // Game board green
    secondary: '#FF6B35',    // Tile accent
    error: '#E91E63',
  },
};

export default function App() {
  return (
    <QueryClientProvider client={queryClient}>
      <PaperProvider theme={theme}>
        <RootNavigator />
      </PaperProvider>
    </QueryClientProvider>
  );
}
```

---

## Type Definitions

### Base Types (`src/types/`)

```typescript
// src/types/tile.ts
export interface Tile {
  letter: string;
  points: number;
  isBlank: boolean;
}

export interface PlacedTile extends Tile {
  x: number;
  y: number;
}

export interface PendingTile extends PlacedTile {
  rackIndex: number; // Track original position for recall
}
```

```typescript
// src/types/game.ts
import type { Tile, PlacedTile } from './tile';

export type GameStatus = 'pending' | 'active' | 'finished';
export type MoveType = 'play' | 'pass' | 'swap' | 'resign';
export type Multiplier = 'TW' | 'DW' | 'TL' | 'DL' | null;

export interface Player {
  id: number;
  username: string;
  score: number;
  rackCount: number;
  isCurrentTurn: boolean;
}

export interface Move {
  id: number;
  userId: number;
  type: MoveType;
  words: string[] | null;
  score: number;
  createdAt: string;
}

export interface Game {
  id: number;
  language: string;
  status: GameStatus;
  board: (PlacedTile | null)[][];
  boardTemplate: Multiplier[][];
  players: Player[];
  myRack: Tile[];
  tilesRemaining: number;
  currentTurnUserId: number;
  winnerId: number | null;
  lastMove: Move | null;
}
```

```typescript
// src/types/user.ts
export interface User {
  id: number;
  username: string;
  email: string;
  gamesPlayed: number;
  gamesWon: number;
}

export interface AuthState {
  user: User | null;
  token: string | null;
  isAuthenticated: boolean;
}
```

---

## Zod Schemas (`src/schemas/`)

Runtime validation with automatic type inference:

```typescript
// src/schemas/game.schema.ts
import { z } from 'zod';

export const TileSchema = z.object({
  letter: z.string().length(1),
  points: z.number().int().min(0).max(10),
  is_blank: z.boolean(),
});

export const PlacedTileSchema = TileSchema.extend({
  x: z.number().int().min(0).max(14),
  y: z.number().int().min(0).max(14),
});

export const PlayerSchema = z.object({
  id: z.number(),
  username: z.string(),
  score: z.number().int(),
  rack_count: z.number().int().min(0).max(7),
  is_current_turn: z.boolean(),
});

export const MoveSchema = z.object({
  id: z.number(),
  user_id: z.number(),
  type: z.enum(['play', 'pass', 'swap', 'resign']),
  words: z.array(z.string()).nullable(),
  score: z.number().int(),
  created_at: z.string(),
});

export const GameSchema = z.object({
  id: z.number(),
  language: z.string(),
  status: z.enum(['pending', 'active', 'finished']),
  board: z.array(z.array(PlacedTileSchema.nullable())),
  board_template: z.array(z.array(z.enum(['TW', 'DW', 'TL', 'DL']).nullable())),
  players: z.array(PlayerSchema),
  my_rack: z.array(TileSchema),
  tiles_remaining: z.number().int(),
  current_turn_user_id: z.number(),
  winner_id: z.number().nullable(),
  last_move: MoveSchema.nullable(),
});

// Infer TypeScript types from schemas
export type GameResponse = z.infer<typeof GameSchema>;

// Transform snake_case to camelCase
export function transformGame(data: GameResponse): Game {
  return {
    id: data.id,
    language: data.language,
    status: data.status,
    board: data.board,
    boardTemplate: data.board_template,
    players: data.players.map(p => ({
      id: p.id,
      username: p.username,
      score: p.score,
      rackCount: p.rack_count,
      isCurrentTurn: p.is_current_turn,
    })),
    myRack: data.my_rack.map(t => ({
      letter: t.letter,
      points: t.points,
      isBlank: t.is_blank,
    })),
    tilesRemaining: data.tiles_remaining,
    currentTurnUserId: data.current_turn_user_id,
    winnerId: data.winner_id,
    lastMove: data.last_move ? {
      id: data.last_move.id,
      userId: data.last_move.user_id,
      type: data.last_move.type,
      words: data.last_move.words,
      score: data.last_move.score,
      createdAt: data.last_move.created_at,
    } : null,
  };
}
```

---

## API Layer (`src/api/`)

### HTTP Client

```typescript
// src/api/client.ts
import axios, { AxiosError } from 'axios';
import { useAuthStore } from '../stores/authStore';

const API_BASE_URL = 'http://wordstockt.com.test/api';

export const apiClient = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
});

// Add auth token to requests
apiClient.interceptors.request.use((config) => {
  const token = useAuthStore.getState().token;
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Handle errors consistently
apiClient.interceptors.response.use(
  (response) => response,
  (error: AxiosError<{ message: string }>) => {
    if (error.response?.status === 401) {
      useAuthStore.getState().logout();
    }
    return Promise.reject(error);
  }
);

// Type-safe API error
export interface ApiError {
  message: string;
  status: number;
}

export function getApiError(error: unknown): ApiError {
  if (axios.isAxiosError(error)) {
    return {
      message: error.response?.data?.message ?? 'Network error',
      status: error.response?.status ?? 500,
    };
  }
  return { message: 'Unknown error', status: 500 };
}
```

### TanStack Query Hooks

```typescript
// src/api/queries/useGame.ts
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { apiClient, getApiError } from '../client';
import { GameSchema, transformGame } from '../../schemas/game.schema';
import type { Game, PlacedTile } from '../../types';

// Query keys factory for type safety
export const gameKeys = {
  all: ['games'] as const,
  lists: () => [...gameKeys.all, 'list'] as const,
  list: (filters: string) => [...gameKeys.lists(), filters] as const,
  details: () => [...gameKeys.all, 'detail'] as const,
  detail: (id: number) => [...gameKeys.details(), id] as const,
};

// Fetch single game
export function useGame(gameId: number) {
  return useQuery({
    queryKey: gameKeys.detail(gameId),
    queryFn: async (): Promise<Game> => {
      const { data } = await apiClient.get(`/games/${gameId}`);
      const validated = GameSchema.parse(data);
      return transformGame(validated);
    },
    staleTime: 30_000, // Consider fresh for 30s
  });
}

// Fetch all games
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
  });
}

// Submit move mutation
interface SubmitMoveParams {
  gameId: number;
  tiles: PlacedTile[];
}

interface MoveResponse {
  game: Game;
  move: { id: number; score: number; words: string[] };
}

export function useSubmitMove() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async ({ gameId, tiles }: SubmitMoveParams): Promise<MoveResponse> => {
      const { data } = await apiClient.post(`/games/${gameId}/moves`, {
        tiles: tiles.map(t => ({
          letter: t.letter,
          points: t.points,
          x: t.x,
          y: t.y,
          is_blank: t.isBlank,
        })),
      });
      return {
        game: transformGame(GameSchema.parse(data.game)),
        move: data.move,
      };
    },
    onSuccess: (data, { gameId }) => {
      // Update cache with new game state
      queryClient.setQueryData(gameKeys.detail(gameId), data.game);
      // Invalidate game list to update scores
      queryClient.invalidateQueries({ queryKey: gameKeys.lists() });
    },
    onError: (error) => {
      // Error handling in component via error state
      console.error('Move failed:', getApiError(error));
    },
  });
}

// Pass turn mutation
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
```

---

## State Management (Zustand)

### Auth Store

```typescript
// src/stores/authStore.ts
import { create } from 'zustand';
import { persist, createJSONStorage } from 'zustand/middleware';
import AsyncStorage from '@react-native-async-storage/async-storage';
import type { User } from '../types';

interface AuthState {
  user: User | null;
  token: string | null;
  isAuthenticated: boolean;
  isLoading: boolean;
}

interface AuthActions {
  setAuth: (user: User, token: string) => void;
  logout: () => void;
  setLoading: (loading: boolean) => void;
}

export const useAuthStore = create<AuthState & AuthActions>()(
  persist(
    (set) => ({
      // State
      user: null,
      token: null,
      isAuthenticated: false,
      isLoading: true,

      // Actions
      setAuth: (user, token) =>
        set({ user, token, isAuthenticated: true, isLoading: false }),

      logout: () =>
        set({ user: null, token: null, isAuthenticated: false, isLoading: false }),

      setLoading: (isLoading) => set({ isLoading }),
    }),
    {
      name: 'auth-storage',
      storage: createJSONStorage(() => AsyncStorage),
      partialize: (state) => ({ token: state.token }), // Only persist token
    }
  )
);
```

### Game Store (for pending tiles)

```typescript
// src/stores/gameStore.ts
import { create } from 'zustand';
import type { Tile, PendingTile } from '../types';

interface GameUIState {
  pendingTiles: PendingTile[];
  selectedRackIndex: number | null;
}

interface GameUIActions {
  placeTile: (tile: Tile, x: number, y: number, rackIndex: number) => void;
  removeTile: (x: number, y: number) => void;
  recallAllTiles: () => void;
  clearPendingTiles: () => void;
  setSelectedRackIndex: (index: number | null) => void;
}

export const useGameStore = create<GameUIState & GameUIActions>((set, get) => ({
  // State
  pendingTiles: [],
  selectedRackIndex: null,

  // Actions
  placeTile: (tile, x, y, rackIndex) =>
    set((state) => ({
      pendingTiles: [
        ...state.pendingTiles,
        { ...tile, x, y, rackIndex },
      ],
      selectedRackIndex: null,
    })),

  removeTile: (x, y) =>
    set((state) => ({
      pendingTiles: state.pendingTiles.filter(
        (t) => t.x !== x || t.y !== y
      ),
    })),

  recallAllTiles: () => set({ pendingTiles: [] }),

  clearPendingTiles: () => set({ pendingTiles: [] }),

  setSelectedRackIndex: (index) => set({ selectedRackIndex: index }),
}));

// Selectors for derived state
export const usePendingTileAt = (x: number, y: number) =>
  useGameStore((state) =>
    state.pendingTiles.find((t) => t.x === x && t.y === y)
  );

export const useRackTileUsed = (rackIndex: number) =>
  useGameStore((state) =>
    state.pendingTiles.some((t) => t.rackIndex === rackIndex)
  );
```

---

## Navigation

```typescript
// src/navigation/types.ts
import type { NativeStackScreenProps } from '@react-navigation/native-stack';

export type RootStackParamList = {
  Login: undefined;
  Register: undefined;
  Home: undefined;
  Game: { gameId: number };
};

// Type-safe screen props
export type LoginScreenProps = NativeStackScreenProps<RootStackParamList, 'Login'>;
export type GameScreenProps = NativeStackScreenProps<RootStackParamList, 'Game'>;

// For useNavigation hook
declare global {
  namespace ReactNavigation {
    interface RootParamList extends RootStackParamList {}
  }
}
```

```typescript
// src/navigation/RootNavigator.tsx
import { NavigationContainer } from '@react-navigation/native';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { useAuthStore } from '../stores/authStore';
import type { RootStackParamList } from './types';

import LoginScreen from '../screens/LoginScreen';
import RegisterScreen from '../screens/RegisterScreen';
import HomeScreen from '../screens/HomeScreen';
import GameScreen from '../screens/GameScreen';

const Stack = createNativeStackNavigator<RootStackParamList>();

export function RootNavigator() {
  const isAuthenticated = useAuthStore((s) => s.isAuthenticated);
  const isLoading = useAuthStore((s) => s.isLoading);

  if (isLoading) {
    return <LoadingScreen />;
  }

  return (
    <NavigationContainer>
      <Stack.Navigator screenOptions={{ headerShown: false }}>
        {isAuthenticated ? (
          <>
            <Stack.Screen name="Home" component={HomeScreen} />
            <Stack.Screen name="Game" component={GameScreen} />
          </>
        ) : (
          <>
            <Stack.Screen name="Login" component={LoginScreen} />
            <Stack.Screen name="Register" component={RegisterScreen} />
          </>
        )}
      </Stack.Navigator>
    </NavigationContainer>
  );
}
```

---

## Components

### GameBoard

```typescript
// src/components/game/GameBoard.tsx
import React, { useCallback } from 'react';
import { View, StyleSheet } from 'react-native';
import { BoardCell } from './BoardCell';
import type { Game, Multiplier, PlacedTile } from '../../types';
import { usePendingTileAt } from '../../stores/gameStore';

interface GameBoardProps {
  game: Game;
  onCellPress: (x: number, y: number) => void;
  isMyTurn: boolean;
}

const BOARD_SIZE = 15;

export function GameBoard({ game, onCellPress, isMyTurn }: GameBoardProps) {
  const renderCell = useCallback(
    (x: number, y: number) => {
      const placedTile = game.board[y]?.[x] ?? null;
      const multiplier = game.boardTemplate[y]?.[x] ?? null;

      return (
        <BoardCell
          key={`${x}-${y}`}
          x={x}
          y={y}
          placedTile={placedTile}
          multiplier={multiplier}
          onPress={() => onCellPress(x, y)}
          disabled={!isMyTurn}
        />
      );
    },
    [game.board, game.boardTemplate, onCellPress, isMyTurn]
  );

  return (
    <View style={styles.board}>
      {Array.from({ length: BOARD_SIZE }, (_, y) => (
        <View key={y} style={styles.row}>
          {Array.from({ length: BOARD_SIZE }, (_, x) => renderCell(x, y))}
        </View>
      ))}
    </View>
  );
}

const styles = StyleSheet.create({
  board: {
    aspectRatio: 1,
    backgroundColor: '#2E7D32',
    padding: 2,
  },
  row: {
    flex: 1,
    flexDirection: 'row',
  },
});
```

### BoardCell

```typescript
// src/components/game/BoardCell.tsx
import React from 'react';
import { TouchableOpacity, View, Text, StyleSheet } from 'react-native';
import { Tile } from './Tile';
import { usePendingTileAt } from '../../stores/gameStore';
import type { Multiplier, PlacedTile } from '../../types';

interface BoardCellProps {
  x: number;
  y: number;
  placedTile: PlacedTile | null;
  multiplier: Multiplier;
  onPress: () => void;
  disabled: boolean;
}

const MULTIPLIER_COLORS: Record<string, string> = {
  TW: '#FF6B35',
  DW: '#E91E63',
  TL: '#2196F3',
  DL: '#03A9F4',
};

const MULTIPLIER_LABELS: Record<string, string> = {
  TW: 'TW',
  DW: 'DW',
  TL: 'TL',
  DL: 'DL',
};

export function BoardCell({
  x,
  y,
  placedTile,
  multiplier,
  onPress,
  disabled,
}: BoardCellProps) {
  const pendingTile = usePendingTileAt(x, y);
  const tile = placedTile ?? pendingTile;
  const isPending = pendingTile != null;

  const backgroundColor = multiplier
    ? MULTIPLIER_COLORS[multiplier]
    : '#D4C4A8';

  return (
    <TouchableOpacity
      style={[styles.cell, { backgroundColor }]}
      onPress={onPress}
      disabled={disabled || placedTile != null}
      activeOpacity={0.7}
    >
      {tile ? (
        <Tile
          letter={tile.letter}
          points={tile.points}
          isPending={isPending}
        />
      ) : multiplier ? (
        <Text style={styles.multiplierText}>
          {MULTIPLIER_LABELS[multiplier]}
        </Text>
      ) : null}
    </TouchableOpacity>
  );
}

const styles = StyleSheet.create({
  cell: {
    flex: 1,
    aspectRatio: 1,
    margin: 1,
    justifyContent: 'center',
    alignItems: 'center',
    borderRadius: 2,
  },
  multiplierText: {
    fontSize: 8,
    fontWeight: 'bold',
    color: '#FFF',
  },
});
```

### Tile

```typescript
// src/components/game/Tile.tsx
import React from 'react';
import { View, Text, StyleSheet, TouchableOpacity } from 'react-native';

interface TileProps {
  letter: string;
  points: number;
  isPending?: boolean;
  onRemove?: () => void;
}

export function Tile({ letter, points, isPending, onRemove }: TileProps) {
  return (
    <View style={[styles.tile, isPending && styles.pendingTile]}>
      <Text style={styles.letter}>{letter}</Text>
      <Text style={styles.points}>{points}</Text>
      {isPending && onRemove && (
        <TouchableOpacity style={styles.removeButton} onPress={onRemove}>
          <Text style={styles.removeText}>×</Text>
        </TouchableOpacity>
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  tile: {
    width: '90%',
    height: '90%',
    backgroundColor: '#E8DCC8',
    borderRadius: 3,
    justifyContent: 'center',
    alignItems: 'center',
    borderWidth: 1,
    borderColor: '#8B7355',
  },
  pendingTile: {
    backgroundColor: '#FFE4B5',
    borderColor: '#FF6B35',
  },
  letter: {
    fontSize: 14,
    fontWeight: 'bold',
    color: '#333',
  },
  points: {
    position: 'absolute',
    bottom: 1,
    right: 2,
    fontSize: 6,
    color: '#666',
  },
  removeButton: {
    position: 'absolute',
    top: -4,
    right: -4,
    width: 14,
    height: 14,
    borderRadius: 7,
    backgroundColor: '#E91E63',
    justifyContent: 'center',
    alignItems: 'center',
  },
  removeText: {
    color: '#FFF',
    fontSize: 10,
    fontWeight: 'bold',
  },
});
```

---

## Screens

### GameScreen

```typescript
// src/screens/GameScreen.tsx
import React, { useCallback } from 'react';
import { View, Text, StyleSheet, Alert } from 'react-native';
import { useGame, useSubmitMove, usePassTurn } from '../api/queries/useGame';
import { useAuthStore } from '../stores/authStore';
import { useGameStore } from '../stores/gameStore';
import { GameBoard } from '../components/game/GameBoard';
import { TileRack } from '../components/game/TileRack';
import { ActionButtons } from '../components/game/ActionButtons';
import { ScoreBar } from '../components/game/ScoreBar';
import type { GameScreenProps } from '../navigation/types';

export default function GameScreen({ route }: GameScreenProps) {
  const { gameId } = route.params;
  const userId = useAuthStore((s) => s.user?.id);

  // Server state
  const { data: game, isLoading, error } = useGame(gameId);
  const submitMove = useSubmitMove();
  const passTurn = usePassTurn();

  // Local UI state
  const pendingTiles = useGameStore((s) => s.pendingTiles);
  const placeTile = useGameStore((s) => s.placeTile);
  const removeTile = useGameStore((s) => s.removeTile);
  const recallAllTiles = useGameStore((s) => s.recallAllTiles);
  const clearPendingTiles = useGameStore((s) => s.clearPendingTiles);
  const selectedRackIndex = useGameStore((s) => s.selectedRackIndex);
  const setSelectedRackIndex = useGameStore((s) => s.setSelectedRackIndex);

  const isMyTurn = game?.currentTurnUserId === userId;
  const canPlay = isMyTurn && pendingTiles.length > 0;

  const handleCellPress = useCallback(
    (x: number, y: number) => {
      if (!game || !isMyTurn) return;

      // If there's a pending tile here, remove it
      const existing = pendingTiles.find((t) => t.x === x && t.y === y);
      if (existing) {
        removeTile(x, y);
        return;
      }

      // If a rack tile is selected, place it
      if (selectedRackIndex !== null) {
        const tile = game.myRack[selectedRackIndex];
        if (tile) {
          placeTile(tile, x, y, selectedRackIndex);
        }
      }
    },
    [game, isMyTurn, pendingTiles, selectedRackIndex, placeTile, removeTile]
  );

  const handlePlay = useCallback(async () => {
    if (!canPlay) return;

    try {
      const result = await submitMove.mutateAsync({
        gameId,
        tiles: pendingTiles,
      });
      clearPendingTiles();
      Alert.alert('Success', `Scored ${result.move.score} points!`);
    } catch (err) {
      const message = err instanceof Error ? err.message : 'Move failed';
      Alert.alert('Invalid Move', message);
    }
  }, [gameId, pendingTiles, canPlay, submitMove, clearPendingTiles]);

  const handlePass = useCallback(async () => {
    try {
      await passTurn.mutateAsync(gameId);
      clearPendingTiles();
    } catch (err) {
      Alert.alert('Error', 'Failed to pass turn');
    }
  }, [gameId, passTurn, clearPendingTiles]);

  if (isLoading) return <LoadingView />;
  if (error || !game) return <ErrorView message="Failed to load game" />;

  return (
    <View style={styles.container}>
      <ScoreBar game={game} currentUserId={userId} />
      <GameBoard
        game={game}
        onCellPress={handleCellPress}
        isMyTurn={isMyTurn}
      />
      <TileRack
        tiles={game.myRack}
        pendingTiles={pendingTiles}
        selectedIndex={selectedRackIndex}
        onTilePress={setSelectedRackIndex}
        disabled={!isMyTurn}
      />
      <ActionButtons
        onRecall={recallAllTiles}
        onPass={handlePass}
        onPlay={handlePlay}
        canPlay={canPlay}
        isLoading={submitMove.isPending}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#F5F5F5',
  },
});
```

---

## UI Components (React Native Paper)

### Reusable Button

```typescript
// src/components/ui/GameButton.tsx
import React from 'react';
import { Button } from 'react-native-paper';
import type { StyleProp, ViewStyle } from 'react-native';

interface GameButtonProps {
  onPress: () => void;
  label: string;
  mode?: 'contained' | 'outlined' | 'text';
  loading?: boolean;
  disabled?: boolean;
  style?: StyleProp<ViewStyle>;
}

export function GameButton({
  onPress,
  label,
  mode = 'contained',
  loading = false,
  disabled = false,
  style,
}: GameButtonProps) {
  return (
    <Button
      mode={mode}
      onPress={onPress}
      loading={loading}
      disabled={disabled || loading}
      style={style}
    >
      {label}
    </Button>
  );
}
```

### Action Buttons

```typescript
// src/components/game/ActionButtons.tsx
import React from 'react';
import { View, StyleSheet } from 'react-native';
import { Button } from 'react-native-paper';

interface ActionButtonsProps {
  onRecall: () => void;
  onPass: () => void;
  onPlay: () => void;
  canPlay: boolean;
  isLoading: boolean;
}

export function ActionButtons({
  onRecall,
  onPass,
  onPlay,
  canPlay,
  isLoading,
}: ActionButtonsProps) {
  return (
    <View style={styles.container}>
      <Button mode="outlined" onPress={onRecall} style={styles.button}>
        Recall
      </Button>
      <Button mode="outlined" onPress={onPass} style={styles.button}>
        Pass
      </Button>
      <Button
        mode="contained"
        onPress={onPlay}
        disabled={!canPlay}
        loading={isLoading}
        style={styles.button}
      >
        Play
      </Button>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flexDirection: 'row',
    justifyContent: 'space-around',
    padding: 16,
    gap: 8,
  },
  button: {
    flex: 1,
  },
});
```

### Login Screen with Paper

```typescript
// src/screens/LoginScreen.tsx
import React, { useState } from 'react';
import { View, StyleSheet } from 'react-native';
import { TextInput, Button, Text, HelperText } from 'react-native-paper';
import { useLogin } from '../api/queries/useAuth';

export default function LoginScreen() {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const login = useLogin();

  const handleLogin = async () => {
    try {
      await login.mutateAsync({ email, password });
    } catch (error) {
      // Error shown via login.error
    }
  };

  return (
    <View style={styles.container}>
      <Text variant="headlineLarge" style={styles.title}>
        WordStockt
      </Text>

      <TextInput
        label="Email"
        value={email}
        onChangeText={setEmail}
        mode="outlined"
        keyboardType="email-address"
        autoCapitalize="none"
        style={styles.input}
      />

      <TextInput
        label="Password"
        value={password}
        onChangeText={setPassword}
        mode="outlined"
        secureTextEntry
        style={styles.input}
      />

      {login.error && (
        <HelperText type="error" visible>
          {login.error.message || 'Login failed'}
        </HelperText>
      )}

      <Button
        mode="contained"
        onPress={handleLogin}
        loading={login.isPending}
        disabled={!email || !password}
        style={styles.button}
      >
        Login
      </Button>

      <Button mode="text" onPress={() => navigation.navigate('Register')}>
        Create Account
      </Button>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    padding: 24,
    justifyContent: 'center',
  },
  title: {
    textAlign: 'center',
    marginBottom: 32,
  },
  input: {
    marginBottom: 16,
  },
  button: {
    marginTop: 8,
    marginBottom: 16,
  },
});
```

### Game List with Cards

```typescript
// src/screens/HomeScreen.tsx
import React from 'react';
import { FlatList, StyleSheet } from 'react-native';
import { Card, Text, FAB, ActivityIndicator } from 'react-native-paper';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useGames } from '../api/queries/useGame';
import { useNavigation } from '@react-navigation/native';
import type { Game } from '../types';

export default function HomeScreen() {
  const navigation = useNavigation();
  const { data: games, isLoading } = useGames();

  const renderGame = ({ item: game }: { item: Game }) => {
    const opponent = game.players.find(p => !p.isCurrentTurn);
    const myPlayer = game.players.find(p => p.isCurrentTurn);

    return (
      <Card
        style={styles.card}
        onPress={() => navigation.navigate('Game', { gameId: game.id })}
      >
        <Card.Title
          title={opponent?.username ?? 'Waiting for opponent'}
          subtitle={`${myPlayer?.score ?? 0} - ${opponent?.score ?? 0}`}
        />
        <Card.Content>
          <Text variant="bodySmall">
            {game.status === 'active'
              ? myPlayer?.isCurrentTurn
                ? 'Your turn'
                : "Opponent's turn"
              : game.status}
          </Text>
        </Card.Content>
      </Card>
    );
  };

  if (isLoading) {
    return <ActivityIndicator style={styles.loader} />;
  }

  return (
    <SafeAreaView style={styles.container}>
      <FlatList
        data={games}
        renderItem={renderGame}
        keyExtractor={(game) => game.id.toString()}
        contentContainerStyle={styles.list}
      />
      <FAB
        icon="plus"
        style={styles.fab}
        onPress={() => {/* Create new game */}}
      />
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
  },
  list: {
    padding: 16,
  },
  card: {
    marginBottom: 12,
  },
  fab: {
    position: 'absolute',
    right: 16,
    bottom: 16,
  },
  loader: {
    flex: 1,
  },
});
```

### Snackbar for Errors

```typescript
// src/components/ui/ErrorSnackbar.tsx
import React from 'react';
import { Snackbar } from 'react-native-paper';

interface ErrorSnackbarProps {
  visible: boolean;
  message: string;
  onDismiss: () => void;
}

export function ErrorSnackbar({ visible, message, onDismiss }: ErrorSnackbarProps) {
  return (
    <Snackbar
      visible={visible}
      onDismiss={onDismiss}
      duration={4000}
      action={{
        label: 'Dismiss',
        onPress: onDismiss,
      }}
    >
      {message}
    </Snackbar>
  );
}
```

---

## Error Handling Pattern

```typescript
// Use Snackbar instead of Alert for better UX
import { useState } from 'react';
import { ErrorSnackbar } from '../components/ui/ErrorSnackbar';
import { getApiError } from '../api/client';

// In your screen component:
const [error, setError] = useState<string | null>(null);

const handlePlay = async () => {
  try {
    await submitMove.mutateAsync({ gameId, tiles });
    clearPendingTiles();
  } catch (err) {
    setError(getApiError(err).message);
  }
};

// In render:
<ErrorSnackbar
  visible={error !== null}
  message={error ?? ''}
  onDismiss={() => setError(null)}
/>
```

---

## Comprehensive Error Handling

### Error Types

```typescript
// src/types/errors.ts

// API error structure (from Laravel)
export interface ApiError {
  message: string;
  status: number;
  errors?: Record<string, string[]>; // Validation errors
}

// App-level error categories
export type ErrorCategory =
  | 'network'      // Connection issues
  | 'auth'         // Authentication/authorization
  | 'validation'   // Form/input validation
  | 'game'         // Game logic errors
  | 'unknown';     // Unexpected errors

export interface AppError {
  category: ErrorCategory;
  message: string;
  userMessage: string;  // User-friendly message
  recoverable: boolean; // Can the user retry?
  originalError?: unknown;
}
```

### Error Utilities

```typescript
// src/utils/errors.ts
import axios, { AxiosError } from 'axios';
import type { ApiError, AppError, ErrorCategory } from '../types/errors';

// Extract API error from axios error
export function getApiError(error: unknown): ApiError {
  if (axios.isAxiosError(error)) {
    const axiosError = error as AxiosError<{ message: string; errors?: Record<string, string[]> }>;
    return {
      message: axiosError.response?.data?.message ?? 'Network error',
      status: axiosError.response?.status ?? 0,
      errors: axiosError.response?.data?.errors,
    };
  }
  if (error instanceof Error) {
    return { message: error.message, status: 0 };
  }
  return { message: 'An unknown error occurred', status: 0 };
}

// Convert to user-friendly app error
export function toAppError(error: unknown): AppError {
  const apiError = getApiError(error);

  // Network/connection error
  if (apiError.status === 0) {
    return {
      category: 'network',
      message: apiError.message,
      userMessage: 'Unable to connect. Check your internet connection.',
      recoverable: true,
    };
  }

  // Authentication error
  if (apiError.status === 401) {
    return {
      category: 'auth',
      message: apiError.message,
      userMessage: 'Your session has expired. Please log in again.',
      recoverable: false,
    };
  }

  // Authorization error
  if (apiError.status === 403) {
    return {
      category: 'auth',
      message: apiError.message,
      userMessage: "You don't have permission to do this.",
      recoverable: false,
    };
  }

  // Validation error
  if (apiError.status === 422) {
    const firstError = apiError.errors
      ? Object.values(apiError.errors)[0]?.[0]
      : apiError.message;
    return {
      category: 'validation',
      message: apiError.message,
      userMessage: firstError ?? 'Please check your input.',
      recoverable: true,
    };
  }

  // Not found
  if (apiError.status === 404) {
    return {
      category: 'unknown',
      message: apiError.message,
      userMessage: 'The requested item was not found.',
      recoverable: false,
    };
  }

  // Server error
  if (apiError.status >= 500) {
    return {
      category: 'unknown',
      message: apiError.message,
      userMessage: 'Something went wrong. Please try again later.',
      recoverable: true,
    };
  }

  // Default
  return {
    category: 'unknown',
    message: apiError.message,
    userMessage: apiError.message || 'An error occurred.',
    recoverable: true,
    originalError: error,
  };
}

// Game-specific error messages
export const GAME_ERROR_MESSAGES: Record<string, string> = {
  'Not your turn': "It's not your turn yet.",
  'Invalid word': 'One or more words are not in the dictionary.',
  'Tiles must be in a line': 'Place all tiles in a straight line.',
  'Gap between tiles': 'No gaps allowed between tiles.',
  'Must connect to existing tiles': 'Your word must connect to tiles on the board.',
  'First move must cover center': 'The first word must cover the center square.',
  'Not enough tiles in bag': 'Not enough tiles left to swap.',
};

export function getGameErrorMessage(error: string): string {
  // Check for known patterns
  for (const [pattern, message] of Object.entries(GAME_ERROR_MESSAGES)) {
    if (error.toLowerCase().includes(pattern.toLowerCase())) {
      return message;
    }
  }
  return error;
}
```

### Global Error Boundary

```typescript
// src/components/ErrorBoundary.tsx
import React, { Component, ErrorInfo, ReactNode } from 'react';
import { View, StyleSheet } from 'react-native';
import { Text, Button } from 'react-native-paper';

interface Props {
  children: ReactNode;
  fallback?: ReactNode;
}

interface State {
  hasError: boolean;
  error: Error | null;
}

export class ErrorBoundary extends Component<Props, State> {
  constructor(props: Props) {
    super(props);
    this.state = { hasError: false, error: null };
  }

  static getDerivedStateFromError(error: Error): State {
    return { hasError: true, error };
  }

  componentDidCatch(error: Error, errorInfo: ErrorInfo) {
    // Log to error reporting service
    console.error('ErrorBoundary caught:', error, errorInfo);
    // Could send to Sentry, Bugsnag, etc.
  }

  handleReset = () => {
    this.setState({ hasError: false, error: null });
  };

  render() {
    if (this.state.hasError) {
      if (this.props.fallback) {
        return this.props.fallback;
      }

      return (
        <View style={styles.container}>
          <Text variant="headlineSmall" style={styles.title}>
            Something went wrong
          </Text>
          <Text style={styles.message}>
            The app encountered an unexpected error.
          </Text>
          <Button mode="contained" onPress={this.handleReset}>
            Try Again
          </Button>
        </View>
      );
    }

    return this.props.children;
  }
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    padding: 24,
  },
  title: {
    marginBottom: 8,
  },
  message: {
    textAlign: 'center',
    marginBottom: 24,
    color: '#666',
  },
});
```

### Query Error Handling

```typescript
// src/api/queryClient.ts
import { QueryClient, QueryCache, MutationCache } from '@tanstack/react-query';
import { useAuthStore } from '../stores/authStore';
import { toAppError } from '../utils/errors';

export const queryClient = new QueryClient({
  queryCache: new QueryCache({
    onError: (error, query) => {
      const appError = toAppError(error);

      // Handle auth errors globally
      if (appError.category === 'auth' && appError.message.includes('401')) {
        useAuthStore.getState().logout();
        return;
      }

      // Log all errors
      console.error(`Query error [${query.queryKey}]:`, appError);
    },
  }),
  mutationCache: new MutationCache({
    onError: (error, variables, context, mutation) => {
      const appError = toAppError(error);

      // Handle auth errors globally
      if (appError.category === 'auth' && appError.message.includes('401')) {
        useAuthStore.getState().logout();
        return;
      }

      console.error(`Mutation error:`, appError);
    },
  }),
  defaultOptions: {
    queries: {
      retry: (failureCount, error) => {
        const appError = toAppError(error);
        // Don't retry auth errors or validation errors
        if (appError.category === 'auth' || appError.category === 'validation') {
          return false;
        }
        // Retry network errors up to 3 times
        return appError.category === 'network' && failureCount < 3;
      },
      staleTime: 30_000,
    },
    mutations: {
      retry: false, // Don't retry mutations by default
    },
  },
});
```

### Form Validation Errors

```typescript
// src/components/ui/FormField.tsx
import React from 'react';
import { View, StyleSheet } from 'react-native';
import { TextInput, HelperText } from 'react-native-paper';
import type { TextInputProps } from 'react-native-paper';

interface FormFieldProps extends Omit<TextInputProps, 'error'> {
  error?: string;
  touched?: boolean;
}

export function FormField({ error, touched, ...props }: FormFieldProps) {
  const showError = touched && !!error;

  return (
    <View style={styles.container}>
      <TextInput
        {...props}
        error={showError}
        mode="outlined"
      />
      <HelperText type="error" visible={showError}>
        {error}
      </HelperText>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    marginBottom: 8,
  },
});
```

```typescript
// Usage with form state
function RegisterScreen() {
  const [form, setForm] = useState({
    username: '',
    email: '',
    password: '',
  });
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [touched, setTouched] = useState<Record<string, boolean>>({});
  const register = useRegister();

  const validate = () => {
    const newErrors: Record<string, string> = {};

    if (!form.username) {
      newErrors.username = 'Username is required';
    } else if (form.username.length < 3) {
      newErrors.username = 'Username must be at least 3 characters';
    }

    if (!form.email) {
      newErrors.email = 'Email is required';
    } else if (!/\S+@\S+\.\S+/.test(form.email)) {
      newErrors.email = 'Invalid email address';
    }

    if (!form.password) {
      newErrors.password = 'Password is required';
    } else if (form.password.length < 8) {
      newErrors.password = 'Password must be at least 8 characters';
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSubmit = async () => {
    setTouched({ username: true, email: true, password: true });

    if (!validate()) return;

    try {
      await register.mutateAsync({
        ...form,
        password_confirmation: form.password,
      });
    } catch (error) {
      const apiError = getApiError(error);
      // Map server validation errors to form fields
      if (apiError.errors) {
        setErrors(prev => ({
          ...prev,
          ...Object.fromEntries(
            Object.entries(apiError.errors!).map(([key, messages]) => [
              key,
              messages[0],
            ])
          ),
        }));
      }
    }
  };

  return (
    <View style={styles.container}>
      <FormField
        label="Username"
        value={form.username}
        onChangeText={(text) => setForm(f => ({ ...f, username: text }))}
        onBlur={() => setTouched(t => ({ ...t, username: true }))}
        error={errors.username}
        touched={touched.username}
      />
      <FormField
        label="Email"
        value={form.email}
        onChangeText={(text) => setForm(f => ({ ...f, email: text }))}
        onBlur={() => setTouched(t => ({ ...t, email: true }))}
        error={errors.email}
        touched={touched.email}
        keyboardType="email-address"
      />
      <FormField
        label="Password"
        value={form.password}
        onChangeText={(text) => setForm(f => ({ ...f, password: text }))}
        onBlur={() => setTouched(t => ({ ...t, password: true }))}
        error={errors.password}
        touched={touched.password}
        secureTextEntry
      />
      <Button
        mode="contained"
        onPress={handleSubmit}
        loading={register.isPending}
      >
        Register
      </Button>
    </View>
  );
}
```

### useError Hook

```typescript
// src/hooks/useError.ts
import { useState, useCallback } from 'react';
import { toAppError, getGameErrorMessage } from '../utils/errors';
import type { AppError } from '../types/errors';

export function useError() {
  const [error, setError] = useState<AppError | null>(null);

  const showError = useCallback((err: unknown) => {
    const appError = toAppError(err);
    // Translate game-specific errors
    if (appError.category === 'validation') {
      appError.userMessage = getGameErrorMessage(appError.userMessage);
    }
    setError(appError);
  }, []);

  const clearError = useCallback(() => {
    setError(null);
  }, []);

  const handleAsync = useCallback(async <T>(
    asyncFn: () => Promise<T>,
    options?: { onError?: (error: AppError) => void }
  ): Promise<T | undefined> => {
    try {
      return await asyncFn();
    } catch (err) {
      const appError = toAppError(err);
      setError(appError);
      options?.onError?.(appError);
      return undefined;
    }
  }, []);

  return {
    error,
    showError,
    clearError,
    handleAsync,
    hasError: error !== null,
    errorMessage: error?.userMessage ?? null,
  };
}
```

```typescript
// Usage in component
function GameScreen({ route }: GameScreenProps) {
  const { error, showError, clearError, handleAsync, errorMessage } = useError();
  const submitMove = useSubmitMove();

  const handlePlay = async () => {
    const result = await handleAsync(
      () => submitMove.mutateAsync({ gameId, tiles: pendingTiles }),
      {
        onError: (err) => {
          if (!err.recoverable) {
            navigation.goBack();
          }
        },
      }
    );

    if (result) {
      clearPendingTiles();
    }
  };

  return (
    <View>
      {/* ... game UI ... */}
      <Snackbar
        visible={!!errorMessage}
        onDismiss={clearError}
        duration={4000}
        action={{ label: 'Dismiss', onPress: clearError }}
      >
        {errorMessage}
      </Snackbar>
    </View>
  );
}
```

### Network Status Handling

```typescript
// src/hooks/useNetworkStatus.ts
import { useEffect, useState } from 'react';
import NetInfo, { NetInfoState } from '@react-native-community/netinfo';

export function useNetworkStatus() {
  const [isConnected, setIsConnected] = useState(true);
  const [isInternetReachable, setIsInternetReachable] = useState(true);

  useEffect(() => {
    const unsubscribe = NetInfo.addEventListener((state: NetInfoState) => {
      setIsConnected(state.isConnected ?? true);
      setIsInternetReachable(state.isInternetReachable ?? true);
    });

    return () => unsubscribe();
  }, []);

  return { isConnected, isInternetReachable, isOffline: !isConnected };
}
```

```typescript
// src/components/ui/OfflineBanner.tsx
import React from 'react';
import { View, StyleSheet } from 'react-native';
import { Text } from 'react-native-paper';
import { useNetworkStatus } from '../../hooks/useNetworkStatus';

export function OfflineBanner() {
  const { isOffline } = useNetworkStatus();

  if (!isOffline) return null;

  return (
    <View style={styles.banner}>
      <Text style={styles.text}>You are offline</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  banner: {
    backgroundColor: '#F44336',
    padding: 8,
    alignItems: 'center',
  },
  text: {
    color: '#FFF',
    fontSize: 12,
  },
});
```

---

## File Structure Summary

```
mobile/src/
├── api/
│   ├── client.ts              # Axios + interceptors
│   ├── queries/
│   │   ├── useAuth.ts         # Login, register, logout
│   │   ├── useGame.ts         # Game CRUD + moves
│   │   └── useGames.ts        # Game list
│   └── types.ts               # API-specific types
├── components/
│   ├── game/
│   │   ├── GameBoard.tsx
│   │   ├── BoardCell.tsx
│   │   ├── Tile.tsx
│   │   ├── TileRack.tsx
│   │   ├── ScoreBar.tsx
│   │   └── ActionButtons.tsx
│   └── ui/
│       ├── Button.tsx
│       ├── Input.tsx
│       └── LoadingView.tsx
├── hooks/
│   ├── useGameState.ts        # Combined game logic
│   └── useWebSocket.ts        # Real-time updates
├── navigation/
│   ├── RootNavigator.tsx
│   └── types.ts               # Param list types
├── schemas/
│   ├── game.schema.ts         # Zod game schemas
│   └── user.schema.ts         # Zod user schemas
├── screens/
│   ├── LoginScreen.tsx
│   ├── RegisterScreen.tsx
│   ├── HomeScreen.tsx
│   └── GameScreen.tsx
├── stores/
│   ├── authStore.ts           # Zustand auth
│   └── gameStore.ts           # Zustand game UI
├── types/
│   ├── game.ts
│   ├── tile.ts
│   └── user.ts
└── utils/
    ├── scoring.ts
    └── validation.ts
```
