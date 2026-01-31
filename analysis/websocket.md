# WordStockt WebSocket Documentation

Real-time communication using Laravel Reverb and React Native WebSocket client.

---

## Table of Contents

1. [Overview](#overview)
2. [Laravel Reverb Setup](#laravel-reverb-setup)
3. [Channel Authorization](#channel-authorization)
4. [Broadcasting Events](#broadcasting-events)
5. [React Native Client](#react-native-client)
6. [Connection Lifecycle](#connection-lifecycle)
7. [Error Handling & Reconnection](#error-handling--reconnection)

---

## Overview

### Architecture

```
┌─────────────────┐    WebSocket     ┌─────────────────┐
│  React Native   │◄────────────────►│  Laravel Reverb │
│     Client      │   ws://localhost │     Server      │
└────────┬────────┘      :8080       └────────┬────────┘
         │                                     │
         │ Subscribe to                        │ Broadcast
         │ private-game.{id}                   │ events
         │                                     │
         ▼                                     ▼
    ┌─────────┐                         ┌─────────────┐
    │ Update  │                         │ Game Action │
    │   UI    │                         │  (move,     │
    └─────────┘                         │   chat)     │
                                        └─────────────┘
```

### Event Flow

1. Player A submits a move via HTTP POST
2. Laravel processes move, updates database
3. Laravel broadcasts `MovePlayed` event to game channel
4. Reverb pushes event to all subscribers
5. Player B's app receives event, updates UI

---

## Laravel Reverb Setup

### Installation

```bash
composer require laravel/reverb
php artisan reverb:install
```

### Configuration

```php
// config/reverb.php
return [
    'default' => env('REVERB_SERVER', 'reverb'),

    'servers' => [
        'reverb' => [
            'host' => env('REVERB_HOST', '0.0.0.0'),
            'port' => env('REVERB_PORT', 8080),
            'hostname' => env('REVERB_HOST', 'localhost'),
            'options' => [
                'tls' => [],
            ],
            'scaling' => [
                'enabled' => env('REVERB_SCALING_ENABLED', false),
            ],
            'pulse_ingest_interval' => env('REVERB_PULSE_INGEST_INTERVAL', 15),
        ],
    ],

    'apps' => [
        [
            'key' => env('REVERB_APP_KEY', 'wordstockt-key'),
            'secret' => env('REVERB_APP_SECRET', 'wordstockt-secret'),
            'app_id' => env('REVERB_APP_ID', 'wordstockt'),
            'options' => [
                'host' => env('REVERB_HOST', 'localhost'),
                'port' => env('REVERB_PORT', 8080),
                'scheme' => env('REVERB_SCHEME', 'http'),
                'useTLS' => env('REVERB_SCHEME', 'http') === 'https',
            ],
            'allowed_origins' => ['*'],
            'ping_interval' => env('REVERB_APP_PING_INTERVAL', 60),
            'max_message_size' => env('REVERB_APP_MAX_MESSAGE_SIZE', 10000),
        ],
    ],
];
```

### Environment Variables

```env
# .env
BROADCAST_CONNECTION=reverb

REVERB_APP_ID=wordstockt
REVERB_APP_KEY=wordstockt-key
REVERB_APP_SECRET=wordstockt-secret
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http
```

### Broadcasting Configuration

```php
// config/broadcasting.php
return [
    'default' => env('BROADCAST_CONNECTION', 'reverb'),

    'connections' => [
        'reverb' => [
            'driver' => 'reverb',
            'key' => env('REVERB_APP_KEY'),
            'secret' => env('REVERB_APP_SECRET'),
            'app_id' => env('REVERB_APP_ID'),
            'options' => [
                'host' => env('REVERB_HOST'),
                'port' => env('REVERB_PORT'),
                'scheme' => env('REVERB_SCHEME', 'http'),
                'useTLS' => env('REVERB_SCHEME', 'http') === 'https',
            ],
        ],
    ],
];
```

### Starting the Server

```bash
# Development
php artisan reverb:start

# Production (with supervisor)
php artisan reverb:start --host=0.0.0.0 --port=8080
```

---

## Channel Authorization

### Channel Routes

```php
// routes/channels.php
use App\Domain\Game\Models\Game;use App\Domain\User\Models\User;use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Game Channel
|--------------------------------------------------------------------------
| Players can only subscribe to games they're participating in.
*/
Broadcast::channel('game.{gameId}', function (User $user, int $gameId) {
    $game = Game::find($gameId);

    if (!$game) {
        return false;
    }

    return $game->players()->where('user_id', $user->id)->exists();
});

/*
|--------------------------------------------------------------------------
| User Channel
|--------------------------------------------------------------------------
| Users can only subscribe to their own private channel.
*/
Broadcast::channel('user.{userId}', function (User $user, int $userId) {
    return $user->id === $userId;
});
```

### Authentication Endpoint

Reverb uses Laravel's built-in broadcasting auth endpoint:

```php
// routes/api.php
Broadcast::routes(['middleware' => ['auth:sanctum']]);
```

This creates `POST /broadcasting/auth` endpoint that validates channel subscriptions.

### Authentication Flow

```
1. Client connects to WebSocket server
2. Client sends subscription request for private-game.{id}
3. Reverb calls /broadcasting/auth with socket_id and channel_name
4. Laravel validates user can access channel (via channels.php)
5. If authorized, returns signed auth token
6. Client receives confirmation, subscription active
```

---

## Broadcasting Events

### MovePlayed Event

```php
<?php

namespace App\Events;

use App\Domain\Game\Models\Game;use App\Domain\Game\Models\Move;use Illuminate\Broadcasting\InteractsWithSockets;use Illuminate\Broadcasting\PrivateChannel;use Illuminate\Contracts\Broadcasting\ShouldBroadcast;use Illuminate\Foundation\Events\Dispatchable;use Illuminate\Queue\SerializesModels;

class MovePlayed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Game $game,
        public Move $move,
    ) {}

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('game.' . $this->game->id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'move.played';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'game' => [
                'id' => $this->game->id,
                'board' => $this->game->board,
                'current_turn_user_id' => $this->game->current_turn_user_id,
                'tiles_remaining' => $this->game->tiles_remaining,
                'status' => $this->game->status->value,
            ],
            'move' => [
                'id' => $this->move->id,
                'user_id' => $this->move->user_id,
                'type' => $this->move->type->value,
                'tiles' => $this->move->tiles,
                'words' => $this->move->words,
                'score' => $this->move->score,
            ],
            'players' => $this->game->players->map(fn($p) => [
                'user_id' => $p->user_id,
                'username' => $p->user->username,
                'score' => $p->score,
                'rack_count' => count($p->rack ?? []),
                'is_current_turn' => $p->user_id === $this->game->current_turn_user_id,
            ]),
        ];
    }
}
```

### MessageSent Event

```php
<?php

namespace App\Events;

use App\Domain\Game\Models\Game;use App\Models\Message;use Illuminate\Broadcasting\PrivateChannel;use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class MessageSent implements ShouldBroadcast
{
    public function __construct(
        public Game $game,
        public Message $message,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('game.' . $this->game->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    public function broadcastWith(): array
    {
        return [
            'message' => [
                'id' => $this->message->id,
                'user_id' => $this->message->user_id,
                'username' => $this->message->user->username,
                'content' => $this->message->content,
                'created_at' => $this->message->created_at->toISOString(),
            ],
        ];
    }
}
```

### GameInvitation Event

```php
<?php

namespace App\Events;

use App\Domain\Game\Models\Game;use App\Domain\User\Models\User;use Illuminate\Broadcasting\PrivateChannel;use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class GameInvitation implements ShouldBroadcast
{
    public function __construct(
        public Game $game,
        public User $inviter,
        public User $invitee,
    ) {}

    public function broadcastOn(): array
    {
        // Broadcast to invitee's personal channel
        return [
            new PrivateChannel('user.' . $this->invitee->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'game.invitation';
    }

    public function broadcastWith(): array
    {
        return [
            'game_id' => $this->game->id,
            'inviter' => [
                'id' => $this->inviter->id,
                'username' => $this->inviter->username,
            ],
            'language' => $this->game->language,
        ];
    }
}
```

### Dispatching Events

```php
// In GameService.php after processing move
use App\Domain\Game\Events\MovePlayed;

public function playMove(Game $game, User $player, array $tiles): Move
{
    // ... validation and processing ...

    $move = Move::create([...]);

    // Broadcast to all players in game
    event(new MovePlayed($game, $move));

    return $move;
}
```

---

## React Native Client

### WebSocket Service

```typescript
// src/services/websocket.ts
import { useAuthStore } from '../stores/authStore';

const WS_HOST = 'ws://localhost:8080';
const APP_KEY = 'wordstockt-key';

type EventCallback = (data: unknown) => void;

interface Channel {
  name: string;
  listeners: Map<string, Set<EventCallback>>;
}

class WebSocketService {
  private socket: WebSocket | null = null;
  private channels: Map<string, Channel> = new Map();
  private socketId: string | null = null;
  private reconnectAttempts = 0;
  private maxReconnectAttempts = 5;
  private reconnectDelay = 1000;
  private pingInterval: NodeJS.Timeout | null = null;

  /**
   * Connect to WebSocket server
   */
  connect(): Promise<void> {
    return new Promise((resolve, reject) => {
      if (this.socket?.readyState === WebSocket.OPEN) {
        resolve();
        return;
      }

      const wsUrl = `${WS_HOST}/app/${APP_KEY}`;
      this.socket = new WebSocket(wsUrl);

      this.socket.onopen = () => {
        console.log('[WS] Connected');
        this.reconnectAttempts = 0;
        this.startPing();
        resolve();
      };

      this.socket.onmessage = (event) => {
        this.handleMessage(JSON.parse(event.data));
      };

      this.socket.onerror = (error) => {
        console.error('[WS] Error:', error);
        reject(error);
      };

      this.socket.onclose = () => {
        console.log('[WS] Disconnected');
        this.stopPing();
        this.handleDisconnect();
      };
    });
  }

  /**
   * Disconnect from server
   */
  disconnect(): void {
    this.stopPing();
    this.channels.clear();
    this.socket?.close();
    this.socket = null;
    this.socketId = null;
  }

  /**
   * Subscribe to a private channel
   */
  async subscribeToGame(gameId: number): Promise<void> {
    const channelName = `private-game.${gameId}`;
    await this.subscribePrivate(channelName);
  }

  /**
   * Subscribe to user's personal channel
   */
  async subscribeToUser(userId: number): Promise<void> {
    const channelName = `private-user.${userId}`;
    await this.subscribePrivate(channelName);
  }

  /**
   * Unsubscribe from a channel
   */
  unsubscribe(channelName: string): void {
    if (!this.channels.has(channelName)) return;

    this.send({
      event: 'pusher:unsubscribe',
      data: { channel: channelName },
    });

    this.channels.delete(channelName);
    console.log(`[WS] Unsubscribed from ${channelName}`);
  }

  /**
   * Listen for an event on a channel
   */
  on(channelName: string, eventName: string, callback: EventCallback): void {
    const channel = this.channels.get(channelName);
    if (!channel) {
      console.warn(`[WS] Not subscribed to ${channelName}`);
      return;
    }

    if (!channel.listeners.has(eventName)) {
      channel.listeners.set(eventName, new Set());
    }
    channel.listeners.get(eventName)!.add(callback);
  }

  /**
   * Remove event listener
   */
  off(channelName: string, eventName: string, callback: EventCallback): void {
    const channel = this.channels.get(channelName);
    if (!channel) return;

    channel.listeners.get(eventName)?.delete(callback);
  }

  // Private methods

  private async subscribePrivate(channelName: string): Promise<void> {
    if (!this.socketId) {
      throw new Error('Not connected to WebSocket server');
    }

    // Get authorization from Laravel
    const auth = await this.authorize(channelName);

    // Subscribe to channel
    this.send({
      event: 'pusher:subscribe',
      data: {
        channel: channelName,
        auth: auth.auth,
      },
    });

    this.channels.set(channelName, {
      name: channelName,
      listeners: new Map(),
    });

    console.log(`[WS] Subscribed to ${channelName}`);
  }

  private async authorize(channelName: string): Promise<{ auth: string }> {
    const token = useAuthStore.getState().token;

    const response = await fetch('http://wordstockt.com.test/broadcasting/auth', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`,
      },
      body: JSON.stringify({
        socket_id: this.socketId,
        channel_name: channelName,
      }),
    });

    if (!response.ok) {
      throw new Error(`Channel authorization failed: ${response.status}`);
    }

    return response.json();
  }

  private handleMessage(message: { event: string; data?: unknown; channel?: string }): void {
    const { event, data, channel } = message;

    // Handle connection established
    if (event === 'pusher:connection_established') {
      const parsed = JSON.parse(data as string);
      this.socketId = parsed.socket_id;
      console.log(`[WS] Socket ID: ${this.socketId}`);
      return;
    }

    // Handle subscription succeeded
    if (event === 'pusher_internal:subscription_succeeded') {
      console.log(`[WS] Subscription confirmed: ${channel}`);
      return;
    }

    // Handle pong
    if (event === 'pusher:pong') {
      return;
    }

    // Handle app events
    if (channel) {
      const ch = this.channels.get(channel);
      if (ch) {
        const listeners = ch.listeners.get(event);
        if (listeners) {
          listeners.forEach((callback) => callback(data));
        }
      }
    }
  }

  private send(message: object): void {
    if (this.socket?.readyState === WebSocket.OPEN) {
      this.socket.send(JSON.stringify(message));
    }
  }

  private handleDisconnect(): void {
    if (this.reconnectAttempts < this.maxReconnectAttempts) {
      this.reconnectAttempts++;
      const delay = this.reconnectDelay * Math.pow(2, this.reconnectAttempts - 1);
      console.log(`[WS] Reconnecting in ${delay}ms (attempt ${this.reconnectAttempts})`);

      setTimeout(() => {
        this.connect().then(() => {
          // Re-subscribe to all channels
          this.channels.forEach((_, channelName) => {
            this.subscribePrivate(channelName);
          });
        });
      }, delay);
    }
  }

  private startPing(): void {
    this.pingInterval = setInterval(() => {
      this.send({ event: 'pusher:ping', data: {} });
    }, 30000);
  }

  private stopPing(): void {
    if (this.pingInterval) {
      clearInterval(this.pingInterval);
      this.pingInterval = null;
    }
  }
}

export const websocketService = new WebSocketService();
```

### React Hook for WebSocket

```typescript
// src/hooks/useWebSocket.ts
import { useEffect, useRef, useCallback } from 'react';
import { useQueryClient } from '@tanstack/react-query';
import { websocketService } from '../services/websocket';
import { gameKeys } from '../api/queries/useGame';
import type { Game, Move } from '../types';

interface MovePlayedEvent {
  game: Partial<Game>;
  move: Move;
  players: Array<{
    user_id: number;
    username: string;
    score: number;
    rack_count: number;
    is_current_turn: boolean;
  }>;
}

interface MessageSentEvent {
  message: {
    id: number;
    user_id: number;
    username: string;
    content: string;
    created_at: string;
  };
}

export function useGameWebSocket(gameId: number) {
  const queryClient = useQueryClient();
  const channelName = `private-game.${gameId}`;
  const subscribedRef = useRef(false);

  const handleMovePlayed = useCallback((data: MovePlayedEvent) => {
    console.log('[WS] Move played:', data);

    // Update game cache with new state
    queryClient.setQueryData(
      gameKeys.detail(gameId),
      (oldData: Game | undefined) => {
        if (!oldData) return oldData;

        return {
          ...oldData,
          board: data.game.board ?? oldData.board,
          currentTurnUserId: data.game.current_turn_user_id ?? oldData.currentTurnUserId,
          tilesRemaining: data.game.tiles_remaining ?? oldData.tilesRemaining,
          status: data.game.status ?? oldData.status,
          players: data.players.map((p) => ({
            id: p.user_id,
            username: p.username,
            score: p.score,
            rackCount: p.rack_count,
            isCurrentTurn: p.is_current_turn,
          })),
          lastMove: {
            id: data.move.id,
            userId: data.move.user_id,
            type: data.move.type,
            words: data.move.words,
            score: data.move.score,
            createdAt: new Date().toISOString(),
          },
        };
      }
    );

    // Invalidate to refetch (gets updated rack for current player)
    queryClient.invalidateQueries({ queryKey: gameKeys.detail(gameId) });
  }, [gameId, queryClient]);

  const handleMessageSent = useCallback((data: MessageSentEvent) => {
    console.log('[WS] Message received:', data);

    // Update messages cache
    queryClient.setQueryData(
      ['games', gameId, 'messages'],
      (oldMessages: Array<{ id: number }> | undefined) => {
        if (!oldMessages) return [data.message];
        return [...oldMessages, data.message];
      }
    );
  }, [gameId, queryClient]);

  useEffect(() => {
    const setup = async () => {
      if (subscribedRef.current) return;

      try {
        await websocketService.connect();
        await websocketService.subscribeToGame(gameId);

        websocketService.on(channelName, 'move.played', handleMovePlayed);
        websocketService.on(channelName, 'message.sent', handleMessageSent);

        subscribedRef.current = true;
      } catch (error) {
        console.error('[WS] Setup failed:', error);
      }
    };

    setup();

    return () => {
      if (subscribedRef.current) {
        websocketService.off(channelName, 'move.played', handleMovePlayed);
        websocketService.off(channelName, 'message.sent', handleMessageSent);
        websocketService.unsubscribe(channelName);
        subscribedRef.current = false;
      }
    };
  }, [gameId, channelName, handleMovePlayed, handleMessageSent]);
}
```

### Using in GameScreen

```typescript
// src/screens/GameScreen.tsx
import { useGameWebSocket } from '../hooks/useWebSocket';

export default function GameScreen({ route }: GameScreenProps) {
  const { gameId } = route.params;

  // Subscribe to real-time updates
  useGameWebSocket(gameId);

  // ... rest of component
}
```

---

## Connection Lifecycle

### Connection States

```typescript
// src/hooks/useConnectionStatus.ts
import { useState, useEffect } from 'react';
import { websocketService } from '../services/websocket';

export type ConnectionStatus = 'connecting' | 'connected' | 'disconnected' | 'reconnecting';

export function useConnectionStatus(): ConnectionStatus {
  const [status, setStatus] = useState<ConnectionStatus>('disconnected');

  useEffect(() => {
    // Listen to connection state changes
    // Implementation depends on exposing state from websocketService
  }, []);

  return status;
}
```

### Connection Status UI

```typescript
// src/components/ui/ConnectionIndicator.tsx
import React from 'react';
import { View, StyleSheet } from 'react-native';
import { Text } from 'react-native-paper';
import { useConnectionStatus } from '../../hooks/useConnectionStatus';

export function ConnectionIndicator() {
  const status = useConnectionStatus();

  if (status === 'connected') return null;

  const colors = {
    connecting: '#FFA500',
    disconnected: '#F44336',
    reconnecting: '#FFA500',
  };

  const messages = {
    connecting: 'Connecting...',
    disconnected: 'Disconnected',
    reconnecting: 'Reconnecting...',
  };

  return (
    <View style={[styles.container, { backgroundColor: colors[status] }]}>
      <Text style={styles.text}>{messages[status]}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    padding: 8,
    alignItems: 'center',
  },
  text: {
    color: '#FFF',
    fontSize: 12,
  },
});
```

### Handling Reconnection

When reconnecting after disconnect:

1. **Automatic re-subscribe**: The service re-subscribes to all active channels
2. **Data refresh**: Invalidate queries to fetch fresh data
3. **Catch up**: Any missed events are compensated by refetching

```typescript
// In websocket service reconnect handler
private async handleReconnection(): Promise<void> {
  // Re-subscribe to all channels
  for (const channelName of this.channels.keys()) {
    await this.subscribePrivate(channelName);
  }

  // Notify app to refresh data
  this.emit('reconnected');
}

// In hook
useEffect(() => {
  const handleReconnect = () => {
    // Refetch current game to catch up on missed events
    queryClient.invalidateQueries({ queryKey: gameKeys.detail(gameId) });
  };

  websocketService.onReconnect(handleReconnect);
  return () => websocketService.offReconnect(handleReconnect);
}, [gameId, queryClient]);
```

---

## Error Handling & Reconnection

### Connection Errors

```typescript
// Types of errors and handling
const handleConnectionError = (error: Error) => {
  if (error.message.includes('401')) {
    // Auth expired - need to re-authenticate
    useAuthStore.getState().logout();
    return;
  }

  if (error.message.includes('Network')) {
    // Network issue - will auto-reconnect
    return;
  }

  // Unknown error - log and attempt reconnect
  console.error('[WS] Unknown error:', error);
};
```

### Subscription Errors

```typescript
// Handle channel authorization failures
const handleSubscriptionError = (channelName: string, error: Error) => {
  if (error.message.includes('403')) {
    // Not authorized for this channel
    console.error(`[WS] Not authorized for ${channelName}`);
    // Remove from channels, don't retry
    return;
  }

  // Retry subscription
  setTimeout(() => {
    this.subscribePrivate(channelName);
  }, 5000);
};
```

### Reconnection Strategy

```typescript
// Exponential backoff with jitter
private calculateReconnectDelay(): number {
  const baseDelay = 1000;
  const maxDelay = 30000;

  const exponentialDelay = baseDelay * Math.pow(2, this.reconnectAttempts);
  const jitter = Math.random() * 1000;

  return Math.min(exponentialDelay + jitter, maxDelay);
}
```

### Handling Missed Events

When connection drops and reconnects, events may be missed. Strategy:

1. **Refetch on reconnect**: Query invalidation triggers fresh data fetch
2. **Last event tracking**: Store last processed event ID, request missed events on reconnect (requires backend support)
3. **Optimistic recovery**: Assume cache is stale, always refetch after reconnect

```typescript
// Simple approach: invalidate all game queries on reconnect
const handleReconnected = () => {
  queryClient.invalidateQueries({ queryKey: gameKeys.all });
};
```

---

## Testing WebSockets

### Unit Testing Service

```typescript
// __tests__/services/websocket.test.ts
import { websocketService } from '@/services/websocket';

// Mock WebSocket
class MockWebSocket {
  onopen: (() => void) | null = null;
  onmessage: ((event: { data: string }) => void) | null = null;
  onclose: (() => void) | null = null;
  readyState = WebSocket.OPEN;

  send = jest.fn();
  close = jest.fn();

  simulateOpen() {
    this.onopen?.();
  }

  simulateMessage(data: object) {
    this.onmessage?.({ data: JSON.stringify(data) });
  }
}

describe('WebSocketService', () => {
  beforeEach(() => {
    (global as any).WebSocket = jest.fn(() => new MockWebSocket());
  });

  it('connects to server', async () => {
    const mockWs = new MockWebSocket();
    (global as any).WebSocket = jest.fn(() => mockWs);

    const connectPromise = websocketService.connect();
    mockWs.simulateOpen();

    await expect(connectPromise).resolves.toBeUndefined();
  });

  it('handles move.played event', async () => {
    const callback = jest.fn();
    const channelName = 'private-game.1';

    websocketService.on(channelName, 'move.played', callback);

    // Simulate receiving event
    // ...
  });
});
```

### Integration Testing

Use Laravel's broadcasting fake for testing event dispatch:

```php
<?php

use App\Domain\Game\Events\MovePlayed;use Illuminate\Support\Facades\Event;

/** @test */
public function it_broadcasts_move_played_event(): void
{
    Event::fake([MovePlayed::class]);

    $game = $this->createActiveGame();
    $this->actingAs($game->currentPlayer->user)
        ->postJson("/api/games/{$game->id}/moves", [
            'tiles' => [...],
        ]);

    Event::assertDispatched(MovePlayed::class, function ($event) use ($game) {
        return $event->game->id === $game->id;
    });
}
```
