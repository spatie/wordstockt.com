<?php

use App\Domain\User\Models\PushToken;
use App\Domain\User\Models\User;

it('registers a push token for authenticated user', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->postJson('/api/auth/push-token', [
            'token' => 'ExponentPushToken[xxxxxxxxxxxxxxxxxxxxxx]',
        ]);

    $response->assertNoContent();

    expect($user->pushTokens)->toHaveCount(1);
    expect($user->pushTokens->first()->token)->toBe('ExponentPushToken[xxxxxxxxxxxxxxxxxxxxxx]');
});

it('registers a push token with device name', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->postJson('/api/auth/push-token', [
            'token' => 'ExponentPushToken[xxxxxxxxxxxxxxxxxxxxxx]',
            'device_name' => 'iPhone 15 Pro',
        ]);

    $response->assertNoContent();

    expect($user->pushTokens->first()->device_name)->toBe('iPhone 15 Pro');
});

it('allows multiple tokens for the same user', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/api/auth/push-token', [
            'token' => 'ExponentPushToken[iphone-token]',
            'device_name' => 'iPhone',
        ]);

    $this->actingAs($user)
        ->postJson('/api/auth/push-token', [
            'token' => 'ExponentPushToken[ipad-token]',
            'device_name' => 'iPad',
        ]);

    expect($user->fresh()->pushTokens)->toHaveCount(2);
});

it('updates existing token instead of creating duplicate', function (): void {
    $user = User::factory()->create();
    $token = 'ExponentPushToken[same-token]';

    $this->actingAs($user)
        ->postJson('/api/auth/push-token', [
            'token' => $token,
            'device_name' => 'Old Name',
        ]);

    $this->actingAs($user)
        ->postJson('/api/auth/push-token', [
            'token' => $token,
            'device_name' => 'New Name',
        ]);

    expect($user->fresh()->pushTokens)->toHaveCount(1);
    expect($user->pushTokens->first()->device_name)->toBe('New Name');
});

it('moves token to new user when device changes owner', function (): void {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $token = 'ExponentPushToken[shared-device]';

    $this->actingAs($user1)
        ->postJson('/api/auth/push-token', ['token' => $token]);

    expect($user1->fresh()->pushTokens)->toHaveCount(1);

    $this->actingAs($user2)
        ->postJson('/api/auth/push-token', ['token' => $token]);

    expect($user1->fresh()->pushTokens)->toHaveCount(0);
    expect($user2->fresh()->pushTokens)->toHaveCount(1);
});

it('validates expo push token format', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->postJson('/api/auth/push-token', [
            'token' => 'invalid-token',
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['token']);
});

it('removes push token on logout when token provided', function (): void {
    $user = User::factory()->create();
    $pushToken = 'ExponentPushToken[to-be-removed]';

    PushToken::create([
        'user_id' => $user->id,
        'token' => $pushToken,
    ]);

    $authToken = $user->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$authToken}")
        ->postJson('/api/auth/logout', [
            'push_token' => $pushToken,
        ]);

    $response->assertOk();
    expect($user->fresh()->pushTokens)->toHaveCount(0);
});

it('keeps other device tokens on logout', function (): void {
    $user = User::factory()->create();

    PushToken::create([
        'user_id' => $user->id,
        'token' => 'ExponentPushToken[iphone]',
        'device_name' => 'iPhone',
    ]);

    PushToken::create([
        'user_id' => $user->id,
        'token' => 'ExponentPushToken[ipad]',
        'device_name' => 'iPad',
    ]);

    $authToken = $user->createToken('test')->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$authToken}")
        ->postJson('/api/auth/logout', [
            'push_token' => 'ExponentPushToken[iphone]',
        ]);

    $remainingTokens = $user->fresh()->pushTokens;
    expect($remainingTokens)->toHaveCount(1);
    expect($remainingTokens->first()->device_name)->toBe('iPad');
});

it('handles logout without push token gracefully', function (): void {
    $user = User::factory()->create();

    PushToken::create([
        'user_id' => $user->id,
        'token' => 'ExponentPushToken[stays]',
    ]);

    $authToken = $user->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$authToken}")
        ->postJson('/api/auth/logout');

    $response->assertOk();
    expect($user->fresh()->pushTokens)->toHaveCount(1);
});

it('returns all tokens via routeNotificationForExpo', function (): void {
    $user = User::factory()->create();

    PushToken::create(['user_id' => $user->id, 'token' => 'ExponentPushToken[token1]']);
    PushToken::create(['user_id' => $user->id, 'token' => 'ExponentPushToken[token2]']);
    PushToken::create(['user_id' => $user->id, 'token' => 'ExponentPushToken[token3]']);

    $tokens = $user->routeNotificationForExpo();

    expect($tokens)->toHaveCount(3);
    expect($tokens[0]->asString())->toBe('ExponentPushToken[token1]');
    expect($tokens[1]->asString())->toBe('ExponentPushToken[token2]');
    expect($tokens[2]->asString())->toBe('ExponentPushToken[token3]');
});

it('returns empty array when user has no tokens', function (): void {
    $user = User::factory()->create();

    $tokens = $user->routeNotificationForExpo();

    expect($tokens)->toBeArray();
    expect($tokens)->toBeEmpty();
});
