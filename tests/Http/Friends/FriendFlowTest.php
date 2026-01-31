<?php

use App\Domain\User\Models\Friend;
use App\Domain\User\Models\User;

describe('Complete friend flow', function (): void {
    it('allows user to search, add, verify, and remove a friend', function (): void {
        $user = User::factory()->create();
        $potentialFriend = User::factory()->create([
            'username' => 'testbuddy',
            'elo_rating' => 1500,
        ]);

        // Step 1: Search for user by username
        $searchResponse = $this->actingAs($user, 'sanctum')
            ->getJson('/api/users/search?query=testbu');

        $searchResponse->assertOk();
        expect($searchResponse->json('data'))->toHaveCount(1)
            ->and($searchResponse->json('data.0.username'))->toBe('testbuddy');

        // Step 2: Check if not friend yet
        $checkResponse = $this->actingAs($user, 'sanctum')
            ->getJson("/api/friends/check/{$potentialFriend->ulid}");

        $checkResponse->assertOk();
        expect($checkResponse->json('is_friend'))->toBeFalse();

        // Step 3: Add as friend
        $addResponse = $this->actingAs($user, 'sanctum')
            ->postJson('/api/friends', [
                'user_ulid' => $potentialFriend->ulid,
            ]);

        $addResponse->assertStatus(201)
            ->assertJsonPath('data.username', 'testbuddy');

        // Step 4: Verify friendship exists
        $verifyResponse = $this->actingAs($user, 'sanctum')
            ->getJson("/api/friends/check/{$potentialFriend->ulid}");

        $verifyResponse->assertOk();
        expect($verifyResponse->json('is_friend'))->toBeTrue();

        // Step 5: Friend appears in friend list
        $listResponse = $this->actingAs($user, 'sanctum')
            ->getJson('/api/friends');

        $listResponse->assertOk();
        expect($listResponse->json('data'))->toHaveCount(1)
            ->and($listResponse->json('data.0.username'))->toBe('testbuddy');

        // Step 6: Remove friend
        $removeResponse = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/friends/{$potentialFriend->ulid}");

        $removeResponse->assertStatus(204);

        // Step 7: Verify removal
        $finalCheckResponse = $this->actingAs($user, 'sanctum')
            ->getJson("/api/friends/check/{$potentialFriend->ulid}");

        $finalCheckResponse->assertOk();
        expect($finalCheckResponse->json('is_friend'))->toBeFalse();

        // Step 8: Friend list is empty
        $emptyListResponse = $this->actingAs($user, 'sanctum')
            ->getJson('/api/friends');

        $emptyListResponse->assertOk();
        expect($emptyListResponse->json('data'))->toHaveCount(0);
    });
});

describe('Mutual friendship scenarios', function (): void {
    it('tracks one-way and mutual friendships correctly', function (): void {
        $alice = User::factory()->create(['username' => 'alice']);
        $bob = User::factory()->create(['username' => 'bob']);

        // Alice adds Bob
        $this->actingAs($alice, 'sanctum')
            ->postJson('/api/friends', ['user_ulid' => $bob->ulid])
            ->assertStatus(201);

        // From Alice's view: Bob is friend
        expect($this->actingAs($alice, 'sanctum')
            ->getJson("/api/friends/check/{$bob->ulid}")
            ->json('is_friend'))->toBeTrue();

        // From Bob's view: Alice is NOT friend yet (one-way)
        expect($this->actingAs($bob, 'sanctum')
            ->getJson("/api/friends/check/{$alice->ulid}")
            ->json('is_friend'))->toBeFalse();

        // Bob adds Alice back (mutual)
        $this->actingAs($bob, 'sanctum')
            ->postJson('/api/friends', ['user_ulid' => $alice->ulid])
            ->assertStatus(201);

        // Now both see each other as friends
        expect($this->actingAs($bob, 'sanctum')
            ->getJson("/api/friends/check/{$alice->ulid}")
            ->json('is_friend'))->toBeTrue();

        // If Alice removes Bob, Bob's friendship with Alice remains
        $this->actingAs($alice, 'sanctum')
            ->deleteJson("/api/friends/{$bob->ulid}")
            ->assertStatus(204);

        expect($this->actingAs($alice, 'sanctum')
            ->getJson("/api/friends/check/{$bob->ulid}")
            ->json('is_friend'))->toBeFalse();

        expect($this->actingAs($bob, 'sanctum')
            ->getJson("/api/friends/check/{$alice->ulid}")
            ->json('is_friend'))->toBeTrue();
    });
});

describe('Friend list ordering and filtering', function (): void {
    it('returns friends in correct order', function (): void {
        $user = User::factory()->create();
        $friend1 = User::factory()->create(['username' => 'alpha']);
        $friend2 = User::factory()->create(['username' => 'beta']);
        $friend3 = User::factory()->create(['username' => 'gamma']);

        // Add friends in different order
        Friend::create(['user_id' => $user->id, 'friend_id' => $friend2->id]);
        Friend::create(['user_id' => $user->id, 'friend_id' => $friend1->id]);
        Friend::create(['user_id' => $user->id, 'friend_id' => $friend3->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/friends');

        $response->assertOk();
        expect($response->json('data'))->toHaveCount(3);
    });

    it('does not include users who are not friends', function (): void {
        $user = User::factory()->create();
        $friend = User::factory()->create();
        $stranger = User::factory()->create();

        Friend::create(['user_id' => $user->id, 'friend_id' => $friend->id]);
        // stranger is not added

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/friends');

        $response->assertOk();
        $friendUlids = collect($response->json('data'))->pluck('friend_ulid');
        expect($friendUlids)->toContain($friend->ulid)
            ->and($friendUlids)->not->toContain($stranger->ulid);
    });
});

describe('Edge cases', function (): void {
    it('handles adding same friend multiple times gracefully', function (): void {
        $user = User::factory()->create();
        $friend = User::factory()->create();

        // First add succeeds
        $this->actingAs($user, 'sanctum')
            ->postJson('/api/friends', ['user_ulid' => $friend->ulid])
            ->assertStatus(201);

        // Second add fails with appropriate message
        $this->actingAs($user, 'sanctum')
            ->postJson('/api/friends', ['user_ulid' => $friend->ulid])
            ->assertStatus(400)
            ->assertJson(['message' => 'Already in friends list.']);

        // Only one friendship record exists
        expect(Friend::where('user_id', $user->id)->count())->toBe(1);
    });

    it('cannot add deleted user as friend', function (): void {
        $user = User::factory()->create();
        $toBeDeleted = User::factory()->create();
        $deletedUlid = $toBeDeleted->ulid;

        $toBeDeleted->delete();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/friends', ['user_ulid' => $deletedUlid]);

        $response->assertStatus(422);
    });
});
