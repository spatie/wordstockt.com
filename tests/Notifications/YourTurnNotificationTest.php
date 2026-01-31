<?php

use App\Domain\Game\Enums\MoveType;
use App\Domain\Game\Models\Move;
use App\Domain\Game\Notifications\YourTurnNotification;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\Notification;

it('sends notification when opponent plays a word', function (): void {
    $game = createGameWithPlayers();
    $player1 = $game->gamePlayers[0]->user;
    $player2 = $game->gamePlayers[1]->user;

    $move = Move::factory()->create([
        'game_id' => $game->id,
        'user_id' => $player1->id,
        'type' => MoveType::Play,
        'words' => ['HELLO', 'WORLD'],
        'score' => 24,
    ]);

    $player2->notify(new YourTurnNotification($game, $move, $player1));

    Notification::assertSentTo($player2, YourTurnNotification::class, function ($notification) use ($game, $move, $player1): true {
        expect($notification->game->id)->toBe($game->id)
            ->and($notification->move->id)->toBe($move->id)
            ->and($notification->madeBy->id)->toBe($player1->id);

        return true;
    });
});

it('sends notification when opponent passes', function (): void {
    $game = createGameWithPlayers();
    $player1 = $game->gamePlayers[0]->user;
    $player2 = $game->gamePlayers[1]->user;

    $move = Move::factory()->create([
        'game_id' => $game->id,
        'user_id' => $player1->id,
        'type' => MoveType::Pass,
        'score' => 0,
    ]);

    $player2->notify(new YourTurnNotification($game, $move, $player1));

    Notification::assertSentTo($player2, YourTurnNotification::class);
});

it('sends notification when opponent swaps tiles', function (): void {
    $game = createGameWithPlayers();
    $player1 = $game->gamePlayers[0]->user;
    $player2 = $game->gamePlayers[1]->user;

    $move = Move::factory()->create([
        'game_id' => $game->id,
        'user_id' => $player1->id,
        'type' => MoveType::Swap,
        'score' => 0,
    ]);

    $player2->notify(new YourTurnNotification($game, $move, $player1));

    Notification::assertSentTo($player2, YourTurnNotification::class);
});

it('builds correct body for play move', function (): void {
    $game = createGameWithPlayers();
    $opponent = User::factory()->create(['username' => 'TestPlayer']);

    $move = Move::factory()->create([
        'game_id' => $game->id,
        'user_id' => $opponent->id,
        'type' => MoveType::Play,
        'words' => ['HELLO', 'WORLD'],
        'score' => 24,
    ]);

    $notification = new YourTurnNotification($game, $move, $opponent);
    $message = $notification->toExpo($game->gamePlayers[0]->user)->toArray();

    expect($message['title'])->toBe('Your turn!')
        ->and($message['body'])->toBe('TestPlayer played HELLO, WORLD for 24 points')
        ->and($message['data'])->toBe(json_encode(['game_ulid' => $game->ulid]));
});

it('builds correct body for pass move', function (): void {
    $game = createGameWithPlayers();
    $opponent = User::factory()->create(['username' => 'TestPlayer']);

    $move = Move::factory()->create([
        'game_id' => $game->id,
        'user_id' => $opponent->id,
        'type' => MoveType::Pass,
        'score' => 0,
    ]);

    $notification = new YourTurnNotification($game, $move, $opponent);
    $message = $notification->toExpo($game->gamePlayers[0]->user)->toArray();

    expect($message['body'])->toBe('TestPlayer passed their turn');
});

it('builds correct body for swap move', function (): void {
    $game = createGameWithPlayers();
    $opponent = User::factory()->create(['username' => 'TestPlayer']);

    $move = Move::factory()->create([
        'game_id' => $game->id,
        'user_id' => $opponent->id,
        'type' => MoveType::Swap,
        'score' => 0,
    ]);

    $notification = new YourTurnNotification($game, $move, $opponent);
    $message = $notification->toExpo($game->gamePlayers[0]->user)->toArray();

    expect($message['body'])->toBe('TestPlayer swapped tiles');
});

it('builds correct message for first turn', function (): void {
    $game = createGameWithPlayers();
    $player = $game->gamePlayers[0]->user;

    $notification = new YourTurnNotification($game);
    $message = $notification->toExpo($player)->toArray();

    expect($message['title'])->toBe('Game started!')
        ->and($message['body'])->toBe('Game started! You can make the first move.')
        ->and($message['data'])->toBe(json_encode(['game_ulid' => $game->ulid]));
});

it('sends notification for first turn without move', function (): void {
    $game = createGameWithPlayers();
    $player = $game->gamePlayers[0]->user;

    $player->notify(new YourTurnNotification($game));

    Notification::assertSentTo($player, YourTurnNotification::class, function ($notification) use ($game): true {
        expect($notification->game->id)->toBe($game->id)
            ->and($notification->move)->toBeNull()
            ->and($notification->madeBy)->toBeNull();

        return true;
    });
});
