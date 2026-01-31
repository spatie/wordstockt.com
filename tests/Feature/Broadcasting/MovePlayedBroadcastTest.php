<?php

use App\Domain\Game\Enums\MoveType;
use App\Domain\Game\Events\MovePlayed;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\Event;

it('MovePlayed event has correct structure', function (): void {
    $user = User::factory()->create();
    $game = createGameWithPlayers(player1: $user);
    $move = \App\Domain\Game\Models\Move::factory()->create([
        'game_id' => $game->id,
        'user_id' => $user->id,
        'type' => MoveType::Play,
    ]);

    $event = new MovePlayed($game, $move, $user);

    expect($event)->toBeInstanceOf(MovePlayed::class)
        ->and($event->game->id)->toBe($game->id)
        ->and($event->move->id)->toBe($move->id)
        ->and($event->player->id)->toBe($user->id);
});

it('broadcasts MovePlayed event when player passes', function (): void {
    Event::fake([MovePlayed::class]);

    $user = User::factory()->create();
    $game = createGameWithPlayers(player1: $user);

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/games/{$game->ulid}/pass");

    Event::assertDispatched(MovePlayed::class, fn ($event): bool => $event->game->id === $game->id
        && $event->player->id === $user->id
        && $event->move->type === MoveType::Pass);
});

it('broadcasts MovePlayed event when player swaps tiles', function (): void {
    Event::fake([MovePlayed::class]);

    $user = User::factory()->create();
    $game = createGameWithPlayers(player1: $user);

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/games/{$game->ulid}/swap", [
            'tiles' => [['letter' => 'A', 'points' => 1]],
        ]);

    Event::assertDispatched(MovePlayed::class, fn ($event): bool => $event->game->id === $game->id
        && $event->player->id === $user->id
        && $event->move->type === MoveType::Swap);
});

it('broadcasts MovePlayed event when player resigns', function (): void {
    Event::fake([MovePlayed::class]);

    $user = User::factory()->create();
    $game = createGameWithPlayers(player1: $user);

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/games/{$game->ulid}/resign");

    Event::assertDispatched(MovePlayed::class, fn ($event): bool => $event->game->id === $game->id
        && $event->player->id === $user->id
        && $event->move->type === MoveType::Resign);
});

it('broadcasts to game channel and opponent user channel', function (): void {
    $user = User::factory()->create();
    $opponent = User::factory()->create();
    $game = createGameWithPlayers(player1: $user, player2: $opponent);

    $event = new MovePlayed($game, $game->moves()->first() ?? new \App\Domain\Game\Models\Move, $user);
    $channels = $event->broadcastOn();

    expect($channels)->toHaveCount(2)
        ->and($channels[0]->name)->toBe('private-game.'.$game->ulid)
        ->and($channels[1]->name)->toBe('private-user.'.$opponent->ulid);
});

it('broadcasts with correct event name', function (): void {
    $user = User::factory()->create();
    $game = createGameWithPlayers(player1: $user);

    $event = new MovePlayed($game, new \App\Domain\Game\Models\Move, $user);

    expect($event->broadcastAs())->toBe('move.played');
});

it('includes game and move data in broadcast payload', function (): void {
    $user = User::factory()->create();
    $game = createGameWithPlayers(player1: $user);

    $move = \App\Domain\Game\Models\Move::factory()->create([
        'game_id' => $game->id,
        'user_id' => $user->id,
        'type' => MoveType::Play,
        'score' => 10,
        'tiles' => [['letter' => 'A', 'x' => 7, 'y' => 7, 'points' => 1]],
        'words' => [['word' => 'A', 'score' => 10]],
    ]);

    $game->refresh();

    $event = new MovePlayed($game->fresh(['gamePlayers.user', 'currentTurnUser', 'winner', 'latestMove.user']), $move, $user);
    $payload = $event->broadcastWith();

    expect($payload)->toHaveKeys(['move', 'game'])
        ->and($payload['move'])->toHaveKeys(['ulid', 'player_ulid', 'player_username', 'tiles', 'words', 'score', 'type', 'created_at'])
        ->and($payload['game'])->toHaveKeys(['ulid', 'board', 'status', 'current_turn_user_ulid', 'winner_ulid', 'tiles_remaining', 'players']);
});
