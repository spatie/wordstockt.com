<?php

use App\Domain\Support\Listeners\HandleFailedExpoNotification;
use App\Domain\User\Models\PushToken;
use App\Domain\User\Models\User;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Notification;
use NotificationChannels\Expo\ExpoChannel;
use NotificationChannels\Expo\ExpoError;
use NotificationChannels\Expo\ExpoErrorType;
use NotificationChannels\Expo\ExpoPushToken;

it('removes token when DeviceNotRegistered error occurs', function (): void {
    $user = User::factory()->create();
    $tokenString = 'ExponentPushToken[invalid-device]';

    PushToken::create([
        'user_id' => $user->id,
        'token' => $tokenString,
    ]);

    $error = ExpoError::make(
        ExpoErrorType::DeviceNotRegistered,
        ExpoPushToken::make($tokenString),
        'The device is no longer registered'
    );

    $event = new NotificationFailed(
        $user,
        new class() extends Notification {},
        ExpoChannel::NAME,
        $error
    );

    $listener = new HandleFailedExpoNotification;
    $listener->handle($event);

    expect(PushToken::where('token', $tokenString)->exists())->toBeFalse();
});

it('keeps token for other error types', function (): void {
    $user = User::factory()->create();
    $tokenString = 'ExponentPushToken[valid-device]';

    PushToken::create([
        'user_id' => $user->id,
        'token' => $tokenString,
    ]);

    $error = ExpoError::make(
        ExpoErrorType::MessageTooBig,
        ExpoPushToken::make($tokenString),
        'Message too big'
    );

    $event = new NotificationFailed(
        $user,
        new class() extends Notification {},
        ExpoChannel::NAME,
        $error
    );

    $listener = new HandleFailedExpoNotification;
    $listener->handle($event);

    expect(PushToken::where('token', $tokenString)->exists())->toBeTrue();
});

it('ignores non-expo channel failures', function (): void {
    $user = User::factory()->create();
    $tokenString = 'ExponentPushToken[should-stay]';

    PushToken::create([
        'user_id' => $user->id,
        'token' => $tokenString,
    ]);

    $event = new NotificationFailed(
        $user,
        new class() extends Notification {},
        'mail',
        ['error' => 'some mail error']
    );

    $listener = new HandleFailedExpoNotification;
    $listener->handle($event);

    expect(PushToken::where('token', $tokenString)->exists())->toBeTrue();
});
