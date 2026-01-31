<?php

use App\Domain\User\Models\Friend;
use App\Domain\User\Models\User;

it('returns true when user is a friend', function (): void {
    $user = User::factory()->create();
    $friend = User::factory()->create();

    Friend::create(['user_id' => $user->id, 'friend_id' => $friend->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/friends/check/{$friend->ulid}");

    $response->assertOk();
    expect($response->json('is_friend'))->toBeTrue();
});

it('returns false when user is not a friend', function (): void {
    $user = User::factory()->create();
    $notFriend = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/friends/check/{$notFriend->ulid}");

    $response->assertOk();
    expect($response->json('is_friend'))->toBeFalse();
});

it('returns false for reverse friendship', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    // Other user added current user as friend, but not vice versa
    Friend::create(['user_id' => $otherUser->id, 'friend_id' => $user->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/friends/check/{$otherUser->ulid}");

    $response->assertOk();
    expect($response->json('is_friend'))->toBeFalse();
});

it('returns 404 for non-existent user', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/friends/check/01h1234567890abcdefghjkmnp');

    $response->assertStatus(404);
});

it('returns 401 for unauthenticated request', function (): void {
    $user = User::factory()->create();

    $response = $this->getJson("/api/friends/check/{$user->ulid}");

    $response->assertStatus(401);
});
