<?php

use App\Domain\Game\Notifications\TurnReminderNotification;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\Notification;

beforeEach(function (): void {
    Notification::fake();
});

it('sends reminder notification', function (): void {
    $game = createGameWithPlayers();
    $player = $game->currentTurnUser;
    $opponent = $game->getOpponent($player);

    $player->notify(new TurnReminderNotification($game, 24, $opponent));

    Notification::assertSentTo($player, TurnReminderNotification::class);
});

it('builds correct title for 24 hour reminder', function (): void {
    $game = createGameWithPlayers();
    $player = $game->currentTurnUser;
    $opponent = $game->getOpponent($player);

    $notification = new TurnReminderNotification($game, 24, $opponent);
    $message = $notification->toExpo($player)->toArray();

    expect($message['title'])->toBe("Clock's ticking!");
});

it('builds correct title for 4 hour reminder', function (): void {
    $game = createGameWithPlayers();
    $player = $game->currentTurnUser;
    $opponent = $game->getOpponent($player);

    $notification = new TurnReminderNotification($game, 4, $opponent);
    $message = $notification->toExpo($player)->toArray();

    expect($message['title'])->toBe("Time's almost up!");
});

it('builds correct title for 1 hour reminder', function (): void {
    $game = createGameWithPlayers();
    $player = $game->currentTurnUser;
    $opponent = $game->getOpponent($player);

    $notification = new TurnReminderNotification($game, 1, $opponent);
    $message = $notification->toExpo($player)->toArray();

    expect($message['title'])->toBe('Last chance!');
});

it('includes winning message when player is ahead', function (): void {
    $game = createGameWithPlayers();
    $player = $game->gamePlayers[0];
    $opponent = $game->gamePlayers[1];

    $player->update(['score' => 100]);
    $opponent->update(['score' => 50]);

    $notification = new TurnReminderNotification($game->fresh(['gamePlayers']), 24, $opponent->user);
    $message = $notification->toExpo($player->user)->toArray();

    expect($message['body'])->toContain('crushing');
});

it('includes losing message when player is behind', function (): void {
    $game = createGameWithPlayers();
    $player = $game->gamePlayers[0];
    $opponent = $game->gamePlayers[1];

    $player->update(['score' => 50]);
    $opponent->update(['score' => 100]);

    $notification = new TurnReminderNotification($game->fresh(['gamePlayers']), 24, $opponent->user);
    $message = $notification->toExpo($player->user)->toArray();

    expect($message['body'])->toContain('prove them wrong');
});

it('includes tied message when scores are equal', function (): void {
    $game = createGameWithPlayers();
    $player = $game->gamePlayers[0];
    $opponent = $game->gamePlayers[1];

    $player->update(['score' => 75]);
    $opponent->update(['score' => 75]);

    $notification = new TurnReminderNotification($game->fresh(['gamePlayers']), 24, $opponent->user);
    $message = $notification->toExpo($player->user)->toArray();

    expect($message['body'])->toContain("anyone's game");
});

it('includes game ulid in notification data', function (): void {
    $game = createGameWithPlayers();
    $player = $game->currentTurnUser;
    $opponent = $game->getOpponent($player);

    $notification = new TurnReminderNotification($game, 24, $opponent);
    $message = $notification->toExpo($player)->toArray();

    expect($message['data'])->toBe(json_encode(['game_ulid' => $game->ulid]));
});

it('includes opponent name in message body', function (): void {
    $game = createGameWithPlayers();
    $player = $game->currentTurnUser;
    $opponent = User::factory()->create(['username' => 'TestOpponent']);
    $game->update(['current_turn_user_id' => $player->id]);

    $notification = new TurnReminderNotification($game, 24, $opponent);
    $message = $notification->toExpo($player)->toArray();

    expect($message['body'])->toContain('TestOpponent');
});
