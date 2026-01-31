<?php

use App\Domain\User\Models\Friend;
use App\Domain\User\Models\User;

it('returns empty array when user has no friends', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/friends');

    $response->assertOk();
    expect($response->json('data'))->toBeEmpty();
});

it('returns list of friends', function (): void {
    $user = User::factory()->create();
    $friend1 = User::factory()->create(['username' => 'friend1', 'elo_rating' => 1300]);
    $friend2 = User::factory()->create(['username' => 'friend2', 'elo_rating' => 1400]);

    Friend::create(['user_id' => $user->id, 'friend_id' => $friend1->id]);
    Friend::create(['user_id' => $user->id, 'friend_id' => $friend2->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/friends');

    $response->assertOk()
        ->assertJsonCount(2, 'data');
});

it('returns friend details in correct format', function (): void {
    $user = User::factory()->create();
    $friend = User::factory()->create([
        'username' => 'testfriend',
        'avatar' => 'https://example.com/avatar.jpg',
        'elo_rating' => 1500,
    ]);

    Friend::create(['user_id' => $user->id, 'friend_id' => $friend->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/friends');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => ['ulid', 'friend_ulid', 'username', 'avatar', 'elo_rating', 'created_at'],
            ],
        ]);

    expect($response->json('data.0.username'))->toBe('testfriend');
    expect($response->json('data.0.avatar'))->toBe('https://example.com/avatar.jpg');
    expect($response->json('data.0.elo_rating'))->toBe(1500);
});

it('does not return friends of other users', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $friend = User::factory()->create();

    Friend::create(['user_id' => $otherUser->id, 'friend_id' => $friend->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/friends');

    $response->assertOk();
    expect($response->json('data'))->toBeEmpty();
});

it('returns 401 for unauthenticated request', function (): void {
    $response = $this->getJson('/api/friends');

    $response->assertStatus(401);
});
