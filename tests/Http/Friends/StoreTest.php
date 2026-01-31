<?php

use App\Domain\User\Models\Friend;
use App\Domain\User\Models\User;

it('adds a user as friend', function (): void {
    $user = User::factory()->create();
    $friendToAdd = User::factory()->create([
        'username' => 'newfriend',
        'avatar' => 'https://example.com/avatar.jpg',
        'elo_rating' => 1400,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/friends', [
            'user_ulid' => $friendToAdd->ulid,
        ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'data' => ['ulid', 'friend_ulid', 'username', 'avatar', 'elo_rating', 'created_at'],
        ]);

    expect($response->json('data.friend_ulid'))->toBe($friendToAdd->ulid);
    expect($response->json('data.username'))->toBe('newfriend');

    $this->assertDatabaseHas('friends', [
        'user_id' => $user->id,
        'friend_id' => $friendToAdd->id,
    ]);
});

it('fails when adding yourself as friend', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/friends', [
            'user_ulid' => $user->ulid,
        ]);

    $response->assertStatus(400)
        ->assertJson(['message' => 'Cannot add yourself as a friend.']);
});

it('fails when user is already a friend', function (): void {
    $user = User::factory()->create();
    $friend = User::factory()->create();

    Friend::create(['user_id' => $user->id, 'friend_id' => $friend->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/friends', [
            'user_ulid' => $friend->ulid,
        ]);

    $response->assertStatus(400)
        ->assertJson(['message' => 'Already in friends list.']);
});

it('fails when user does not exist', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/friends', [
            'user_ulid' => '01h1234567890abcdefghjkmnp',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['user_ulid']);
});

it('fails when user_ulid is missing', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/friends', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['user_ulid']);
});

it('allows mutual friendships', function (): void {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    // User 2 adds User 1
    Friend::create(['user_id' => $user2->id, 'friend_id' => $user1->id]);

    // User 1 can still add User 2
    $response = $this->actingAs($user1, 'sanctum')
        ->postJson('/api/friends', [
            'user_ulid' => $user2->ulid,
        ]);

    $response->assertStatus(201);

    $this->assertDatabaseHas('friends', [
        'user_id' => $user1->id,
        'friend_id' => $user2->id,
    ]);
});

it('returns 401 for unauthenticated request', function (): void {
    $user = User::factory()->create();

    $response = $this->postJson('/api/friends', [
        'user_ulid' => $user->ulid,
    ]);

    $response->assertStatus(401);
});
