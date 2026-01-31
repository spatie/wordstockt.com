<?php

use App\Domain\User\Models\Friend;
use App\Domain\User\Models\User;

it('removes a friend', function (): void {
    $user = User::factory()->create();
    $friend = User::factory()->create();

    Friend::create(['user_id' => $user->id, 'friend_id' => $friend->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/friends/{$friend->ulid}");

    $response->assertStatus(204);

    $this->assertDatabaseMissing('friends', [
        'user_id' => $user->id,
        'friend_id' => $friend->id,
    ]);
});

it('fails when friend relationship does not exist', function (): void {
    $user = User::factory()->create();
    $notFriend = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/friends/{$notFriend->ulid}");

    $response->assertStatus(404)
        ->assertJson(['message' => 'Friend not found.']);
});

it('does not affect reverse friendship', function (): void {
    $user = User::factory()->create();
    $friend = User::factory()->create();

    // Both users are friends with each other
    Friend::create(['user_id' => $user->id, 'friend_id' => $friend->id]);
    Friend::create(['user_id' => $friend->id, 'friend_id' => $user->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/friends/{$friend->ulid}");

    $response->assertStatus(204);

    // User's friendship is removed
    $this->assertDatabaseMissing('friends', [
        'user_id' => $user->id,
        'friend_id' => $friend->id,
    ]);

    // Friend's friendship remains
    $this->assertDatabaseHas('friends', [
        'user_id' => $friend->id,
        'friend_id' => $user->id,
    ]);
});

it('returns 404 for non-existent user', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->deleteJson('/api/friends/01h1234567890abcdefghjkmnp');

    $response->assertStatus(404);
});

it('returns 401 for unauthenticated request', function (): void {
    $user = User::factory()->create();

    $response = $this->deleteJson("/api/friends/{$user->ulid}");

    $response->assertStatus(401);
});
