<?php

use App\Domain\Game\Enums\GameStatus;
use App\Domain\Game\Enums\MoveType;
use App\Domain\Game\Models\Move;
use App\Domain\Game\Notifications\TurnReminderNotification;
use App\Domain\Game\Notifications\TurnTimedOutNotification;
use Illuminate\Support\Facades\Notification;

beforeEach(function (): void {
    Notification::fake();
});

it('auto-passes games that have expired', function (): void {
    $game = createGameWithPlayers();
    $game->update([
        'turn_expires_at' => now()->subHour(),
    ]);

    $this->artisan('games:auto-pass-expired-turns')->assertSuccessful();

    expect($game->fresh()->moves)->toHaveCount(1);
});

it('does not auto-pass games that have not expired', function (): void {
    $game = createGameWithPlayers();
    $game->update([
        'turn_expires_at' => now()->addHours(48),
    ]);

    $this->artisan('games:auto-pass-expired-turns')->assertSuccessful();

    expect($game->fresh()->moves)->toHaveCount(0);
});

it('sends 24 hour reminder', function (): void {
    $game = createGameWithPlayers();
    $game->update([
        'turn_expires_at' => now()->addHours(24)->addMinutes(30),
    ]);

    $this->artisan('games:send-turn-reminders')->assertSuccessful();

    Notification::assertSentTo(
        $game->currentTurnUser,
        TurnReminderNotification::class,
        fn ($notification): bool => $notification->hoursRemaining === 24
    );
});

it('sends 4 hour reminder', function (): void {
    $game = createGameWithPlayers();
    $game->update([
        'turn_expires_at' => now()->addHours(4)->addMinutes(30),
    ]);

    $this->artisan('games:send-turn-reminders')->assertSuccessful();

    Notification::assertSentTo(
        $game->currentTurnUser,
        TurnReminderNotification::class,
        fn ($notification): bool => $notification->hoursRemaining === 4
    );
});

it('sends 1 hour reminder', function (): void {
    $game = createGameWithPlayers();
    $game->update([
        'turn_expires_at' => now()->addHours(1)->addMinutes(30),
    ]);

    $this->artisan('games:send-turn-reminders')->assertSuccessful();

    Notification::assertSentTo(
        $game->currentTurnUser,
        TurnReminderNotification::class,
        fn ($notification): bool => $notification->hoursRemaining === 1
    );
});

it('does not send reminder for finished games', function (): void {
    $game = createGameWithPlayers();
    $game->update([
        'status' => GameStatus::Finished,
        'turn_expires_at' => now()->addHours(24),
    ]);

    $this->artisan('games:send-turn-reminders')->assertSuccessful();

    Notification::assertNotSentTo($game->currentTurnUser, TurnReminderNotification::class);
});

it('does not send duplicate reminders', function (): void {
    $game = createGameWithPlayers();
    $game->update([
        'turn_expires_at' => now()->addHours(24)->addMinutes(30),
    ]);

    $this->artisan('games:send-turn-reminders')->assertSuccessful();
    $this->artisan('games:send-turn-reminders')->assertSuccessful();

    Notification::assertSentToTimes($game->currentTurnUser, TurnReminderNotification::class, 1);
    expect($game->fresh()->last_turn_reminder_sent)->toBe(24);
});

it('does not send reminder for pending games', function (): void {
    $game = createGameWithPlayers(status: GameStatus::Pending);
    $game->update([
        'turn_expires_at' => now()->addHours(24),
        'current_turn_user_id' => null,
    ]);

    $this->artisan('games:send-turn-reminders')->assertSuccessful();

    Notification::assertNothingSent();
});

it('notifies timed out player when auto-passing', function (): void {
    $game = createGameWithPlayers();
    $timedOutPlayer = $game->currentTurnUser;
    $game->update([
        'turn_expires_at' => now()->subHour(),
    ]);

    $this->artisan('games:auto-pass-expired-turns')->assertSuccessful();

    Notification::assertSentTo($timedOutPlayer, TurnTimedOutNotification::class);
});

it('ends game after 4 consecutive auto-passes', function (): void {
    $game = createGameWithPlayers();

    // Create 3 pass moves
    Move::factory()->count(3)->create([
        'game_id' => $game->id,
        'user_id' => $game->players->first()->id,
        'type' => MoveType::Pass,
    ]);

    $game->update([
        'turn_expires_at' => now()->subHour(),
    ]);

    $this->artisan('games:auto-pass-expired-turns')->assertSuccessful();

    expect($game->fresh()->status)->toBe(GameStatus::Finished);
});

it('does not send reminder when game has no current turn user', function (): void {
    $game = createGameWithPlayers();
    $game->update([
        'turn_expires_at' => now()->addHours(24),
        'current_turn_user_id' => null,
    ]);

    $this->artisan('games:send-turn-reminders')->assertSuccessful();

    Notification::assertNothingSent();
});

it('sends 4h reminder after 24h reminder was already sent', function (): void {
    $game = createGameWithPlayers();
    $game->update([
        'turn_expires_at' => now()->addHours(4)->addMinutes(30),
        'last_turn_reminder_sent' => 24,
    ]);

    $this->artisan('games:send-turn-reminders')->assertSuccessful();

    Notification::assertSentTo(
        $game->currentTurnUser,
        TurnReminderNotification::class,
        fn ($notification): bool => $notification->hoursRemaining === 4
    );
    expect($game->fresh()->last_turn_reminder_sent)->toBe(4);
});

it('does not send 24h reminder if 4h reminder was already sent', function (): void {
    $game = createGameWithPlayers();
    $game->update([
        'turn_expires_at' => now()->addHours(24)->addMinutes(30),
        'last_turn_reminder_sent' => 4,
    ]);

    $this->artisan('games:send-turn-reminders')->assertSuccessful();

    Notification::assertNotSentTo($game->currentTurnUser, TurnReminderNotification::class);
});

it('does not send reminder when turn expires in more than 25 hours', function (): void {
    $game = createGameWithPlayers();
    $game->update([
        'turn_expires_at' => now()->addHours(30),
    ]);

    $this->artisan('games:send-turn-reminders')->assertSuccessful();

    Notification::assertNothingSent();
});

it('does not send reminder when turn expires in less than 1 hour', function (): void {
    $game = createGameWithPlayers();
    $game->update([
        'turn_expires_at' => now()->addMinutes(30),
    ]);

    $this->artisan('games:send-turn-reminders')->assertSuccessful();

    Notification::assertNothingSent();
});

it('resets reminder tracking when turn switches after auto-pass', function (): void {
    $game = createGameWithPlayers();
    $game->update([
        'turn_expires_at' => now()->subHour(),
        'last_turn_reminder_sent' => 1,
    ]);

    $this->artisan('games:auto-pass-expired-turns')->assertSuccessful();

    expect($game->fresh()->last_turn_reminder_sent)->toBeNull();
});
