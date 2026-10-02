<?php

use App\Domain\Game\Models\Game;
use App\Domain\Game\Notifications\GameFinishedNotification;
use App\Domain\Game\Notifications\GameInvitationNotification;
use App\Domain\Game\Notifications\GameInviteAcceptedNotification;
use App\Domain\Game\Notifications\TurnReminderNotification;
use App\Domain\Game\Notifications\TurnTimedOutNotification;
use App\Domain\Game\Notifications\YourTurnNotification;
use App\Domain\User\Mail\ResetPasswordMail;
use App\Domain\User\Models\User;
use App\Domain\User\Notifications\VerifyEmailNotification;

dataset('queued notifications', [
    'your turn' => fn (Game $game, User $user): YourTurnNotification => new YourTurnNotification($game),
    'game finished' => fn (Game $game, User $user): GameFinishedNotification => new GameFinishedNotification($game),
    'game invitation' => fn (Game $game, User $user): GameInvitationNotification => new GameInvitationNotification($game, $user),
    'game invite accepted' => fn (Game $game, User $user): GameInviteAcceptedNotification => new GameInviteAcceptedNotification($game, $user),
    'turn timed out' => fn (Game $game, User $user): TurnTimedOutNotification => new TurnTimedOutNotification($game),
    'turn reminder' => fn (Game $game, User $user): TurnReminderNotification => new TurnReminderNotification($game, 2, $user),
    'verify email' => fn (Game $game, User $user): VerifyEmailNotification => new VerifyEmailNotification($user),
    'reset password' => fn (Game $game, User $user): ResetPasswordMail => new ResetPasswordMail('token', $user),
]);

it('puts notifications on the notifications queue by default', function (Closure $makeNotification): void {
    $game = createGameWithPlayers();

    $notification = $makeNotification($game, $game->gamePlayers[0]->user);

    expect($notification->queue)->toBe('notifications');
})->with('queued notifications');

it('puts notifications on the configured queue', function (Closure $makeNotification): void {
    config()->set('queue.notifications_queue', 'default');

    $game = createGameWithPlayers();

    $notification = $makeNotification($game, $game->gamePlayers[0]->user);

    expect($notification->queue)->toBe('default');
})->with('queued notifications');
