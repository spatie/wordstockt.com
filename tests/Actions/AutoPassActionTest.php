<?php

use App\Domain\Game\Actions\AutoPassAction;
use App\Domain\Game\Enums\GameStatus;
use App\Domain\Game\Enums\MoveType;
use App\Domain\Game\Models\Move;
use App\Domain\Game\Notifications\TurnTimedOutNotification;
use App\Domain\Game\Notifications\YourTurnNotification;
use Illuminate\Support\Facades\Notification;

beforeEach(function (): void {
    Notification::fake();
});

it('creates a pass move for the current player', function (): void {
    $game = createGameWithPlayers();
    $currentPlayer = $game->currentTurnUser;

    $move = app(AutoPassAction::class)->execute($game);

    expect($move->user_id)->toBe($currentPlayer->id)
        ->and($move->type)->toBe(MoveType::Pass)
        ->and($move->score)->toBe(0);
});

it('increments consecutive passes', function (): void {
    $game = createGameWithPlayers();
    $initialPasses = $game->consecutive_passes;

    app(AutoPassAction::class)->execute($game);

    expect($game->fresh()->consecutive_passes)->toBe($initialPasses + 1);
});

it('switches turn to the next player', function (): void {
    $game = createGameWithPlayers();
    $player1 = $game->gamePlayers[0]->user;
    $player2 = $game->gamePlayers[1]->user;

    $game->update(['current_turn_user_id' => $player1->id]);

    app(AutoPassAction::class)->execute($game);

    expect($game->fresh()->current_turn_user_id)->toBe($player2->id);
});

it('sets new turn_expires_at when switching turn', function (): void {
    $game = createGameWithPlayers();
    $oldExpiresAt = now()->subHour();
    $game->update(['turn_expires_at' => $oldExpiresAt]);

    app(AutoPassAction::class)->execute($game);

    expect($game->fresh()->turn_expires_at)->toBeGreaterThan(now());
});

it('notifies the timed-out player', function (): void {
    $game = createGameWithPlayers();
    $timedOutPlayer = $game->currentTurnUser;

    app(AutoPassAction::class)->execute($game);

    Notification::assertSentTo($timedOutPlayer, TurnTimedOutNotification::class);
});

it('notifies opponent when turn switches', function (): void {
    $game = createGameWithPlayers();
    $opponent = $game->getOpponent($game->currentTurnUser);

    app(AutoPassAction::class)->execute($game);

    Notification::assertSentTo($opponent, YourTurnNotification::class);
});

it('ends game after 4 consecutive passes', function (): void {
    $game = createGameWithPlayers();

    // Create 3 pass moves
    Move::factory()->count(3)->create([
        'game_id' => $game->id,
        'user_id' => $game->players->first()->id,
        'type' => MoveType::Pass,
    ]);

    app(AutoPassAction::class)->execute($game);

    expect($game->fresh()->status)->toBe(GameStatus::Finished);
});

it('does not notify opponent with YourTurnNotification when game ends', function (): void {
    $game = createGameWithPlayers();

    // Create 3 pass moves
    Move::factory()->count(3)->create([
        'game_id' => $game->id,
        'user_id' => $game->players->first()->id,
        'type' => MoveType::Pass,
    ]);

    $opponent = $game->getOpponent($game->currentTurnUser);

    app(AutoPassAction::class)->execute($game);

    Notification::assertNotSentTo($opponent, YourTurnNotification::class);
});
