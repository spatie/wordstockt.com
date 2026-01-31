<?php

use App\Domain\Game\Enums\GameStatus;
use App\Domain\Game\Notifications\GameFinishedNotification;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\Notification;

it('sends notification when game finishes', function (): void {
    $player1 = User::factory()->create();
    $player2 = User::factory()->create();
    $game = createGameWithPlayers(player1: $player1, player2: $player2, status: GameStatus::Finished);
    $game->update(['winner_id' => $player1->id]);
    $game->gamePlayers()->where('user_id', $player1->id)->update(['score' => 100]);
    $game->gamePlayers()->where('user_id', $player2->id)->update(['score' => 80]);
    $game->refresh()->load('gamePlayers');

    $player1->notify(new GameFinishedNotification($game));

    Notification::assertSentTo($player1, GameFinishedNotification::class);
});

it('builds correct body for winner', function (): void {
    $player1 = User::factory()->create(['username' => 'Winner']);
    $player2 = User::factory()->create(['username' => 'Loser']);
    $game = createGameWithPlayers(player1: $player1, player2: $player2, status: GameStatus::Finished);
    $game->update(['winner_id' => $player1->id]);
    $game->gamePlayers()->where('user_id', $player1->id)->update(['score' => 150]);
    $game->gamePlayers()->where('user_id', $player2->id)->update(['score' => 120]);
    $game->refresh()->load('gamePlayers', 'players');

    $notification = new GameFinishedNotification($game);
    $message = $notification->toExpo($player1)->toArray();

    expect($message['title'])->toBe('Game finished!')
        ->and($message['body'])->toBe('You beat Loser! Final: 150 - 120')
        ->and($message['data'])->toBe(json_encode(['game_ulid' => $game->ulid]));
});

it('builds correct body for loser', function (): void {
    $player1 = User::factory()->create(['username' => 'Winner']);
    $player2 = User::factory()->create(['username' => 'Loser']);
    $game = createGameWithPlayers(player1: $player1, player2: $player2, status: GameStatus::Finished);
    $game->update(['winner_id' => $player1->id]);
    $game->gamePlayers()->where('user_id', $player1->id)->update(['score' => 150]);
    $game->gamePlayers()->where('user_id', $player2->id)->update(['score' => 120]);
    $game->refresh()->load('gamePlayers', 'players');

    $notification = new GameFinishedNotification($game);
    $message = $notification->toExpo($player2)->toArray();

    expect($message['body'])->toBe('Winner wins. Final: 120 - 150');
});

it('builds correct body for resign', function (): void {
    $player1 = User::factory()->create(['username' => 'Winner']);
    $player2 = User::factory()->create(['username' => 'Resigner']);
    $game = createGameWithPlayers(player1: $player1, player2: $player2, status: GameStatus::Finished);
    $game->update(['winner_id' => $player1->id]);
    $game->refresh()->load('gamePlayers', 'players');

    $notification = new GameFinishedNotification($game, wasResign: true, resignedPlayer: $player2);
    $message = $notification->toExpo($player1)->toArray();

    expect($message['body'])->toBe('Resigner resigned. You win!');
});
