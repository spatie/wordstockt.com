<?php

use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\GamePlayer;
use App\Domain\Game\Models\HeadToHeadStats;
use App\Domain\Game\Models\Move;
use App\Domain\User\Models\EloHistory;
use App\Domain\User\Models\Friend;
use App\Domain\User\Models\GameInvitation;
use App\Domain\User\Models\PushToken;
use App\Domain\User\Models\User;
use App\Domain\User\Models\UserStatistics;

it('deletes authenticated user account', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->deleteJson('/api/auth/user');

    $response->assertOk()
        ->assertJson(['message' => 'Account deleted successfully']);

    expect(User::find($user->id))->toBeNull();
});

it('deletes user push tokens', function (): void {
    $user = User::factory()->create();

    PushToken::create([
        'user_id' => $user->id,
        'token' => 'ExponentPushToken[xxx]',
        'device_name' => 'Test Device',
    ]);

    expect(PushToken::where('user_id', $user->id)->count())->toBe(1);

    $this->actingAs($user, 'sanctum')
        ->deleteJson('/api/auth/user');

    expect(PushToken::where('user_id', $user->id)->count())->toBe(0);
});

it('deletes user api tokens', function (): void {
    $user = User::factory()->create();
    $user->createToken('token-1');
    $user->createToken('token-2');

    expect($user->tokens()->count())->toBe(2);

    $this->actingAs($user, 'sanctum')
        ->deleteJson('/api/auth/user');

    expect($user->tokens()->count())->toBe(0);
});

it('deletes user friendships', function (): void {
    $user = User::factory()->create();
    $friend1 = User::factory()->create();
    $friend2 = User::factory()->create();

    Friend::create(['user_id' => $user->id, 'friend_id' => $friend1->id]);
    Friend::create(['user_id' => $friend2->id, 'friend_id' => $user->id]);

    expect(Friend::where('user_id', $user->id)->orWhere('friend_id', $user->id)->count())->toBe(2);

    $this->actingAs($user, 'sanctum')
        ->deleteJson('/api/auth/user');

    expect(Friend::where('user_id', $user->id)->orWhere('friend_id', $user->id)->count())->toBe(0);
});

it('deletes user game invitations', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $game = Game::factory()->create();

    GameInvitation::create([
        'game_id' => $game->id,
        'inviter_id' => $user->id,
        'invitee_id' => $otherUser->id,
        'status' => 'pending',
    ]);

    GameInvitation::create([
        'game_id' => $game->id,
        'inviter_id' => $otherUser->id,
        'invitee_id' => $user->id,
        'status' => 'pending',
    ]);

    expect(GameInvitation::where('inviter_id', $user->id)->orWhere('invitee_id', $user->id)->count())->toBe(2);

    $this->actingAs($user, 'sanctum')
        ->deleteJson('/api/auth/user');

    expect(GameInvitation::where('inviter_id', $user->id)->orWhere('invitee_id', $user->id)->count())->toBe(0);
});

it('deletes user statistics', function (): void {
    $user = User::factory()->create();

    UserStatistics::create([
        'user_id' => $user->id,
        'highest_scoring_word' => 'TEST',
        'highest_scoring_word_score' => 10,
    ]);

    expect(UserStatistics::where('user_id', $user->id)->count())->toBe(1);

    $this->actingAs($user, 'sanctum')
        ->deleteJson('/api/auth/user');

    expect(UserStatistics::where('user_id', $user->id)->count())->toBe(0);
});

it('deletes user elo history', function (): void {
    $user = User::factory()->create();
    $game = Game::factory()->create();

    EloHistory::create([
        'user_id' => $user->id,
        'game_id' => $game->id,
        'elo_before' => 1200,
        'elo_after' => 1220,
        'elo_change' => 20,
    ]);

    expect(EloHistory::where('user_id', $user->id)->count())->toBe(1);

    $this->actingAs($user, 'sanctum')
        ->deleteJson('/api/auth/user');

    expect(EloHistory::where('user_id', $user->id)->count())->toBe(0);
});

it('deletes user head to head stats', function (): void {
    $user = User::factory()->create();
    $opponent = User::factory()->create();

    HeadToHeadStats::create([
        'user_id' => $user->id,
        'opponent_id' => $opponent->id,
        'wins' => 5,
        'losses' => 3,
        'draws' => 0,
    ]);

    HeadToHeadStats::create([
        'user_id' => $opponent->id,
        'opponent_id' => $user->id,
        'wins' => 3,
        'losses' => 5,
        'draws' => 0,
    ]);

    expect(HeadToHeadStats::where('user_id', $user->id)->orWhere('opponent_id', $user->id)->count())->toBe(2);

    $this->actingAs($user, 'sanctum')
        ->deleteJson('/api/auth/user');

    expect(HeadToHeadStats::where('user_id', $user->id)->orWhere('opponent_id', $user->id)->count())->toBe(0);
});

it('deletes user moves', function (): void {
    $user = User::factory()->create();
    $game = Game::factory()->create();

    Move::create([
        'game_id' => $game->id,
        'user_id' => $user->id,
        'type' => 'play',
        'tiles' => json_encode([]),
        'score' => 10,
    ]);

    expect(Move::where('user_id', $user->id)->count())->toBe(1);

    $this->actingAs($user, 'sanctum')
        ->deleteJson('/api/auth/user');

    expect(Move::where('user_id', $user->id)->count())->toBe(0);
});

it('clears winner_id on games won by deleted user', function (): void {
    $user = User::factory()->create();
    $game = Game::factory()->create(['winner_id' => $user->id]);

    expect($game->winner_id)->toBe($user->id);

    $this->actingAs($user, 'sanctum')
        ->deleteJson('/api/auth/user');

    expect($game->fresh()->winner_id)->toBeNull();
});

it('deletes user game players', function (): void {
    $user = User::factory()->create();
    $game = Game::factory()->create();

    GamePlayer::create([
        'game_id' => $game->id,
        'user_id' => $user->id,
        'turn_order' => 1,
        'score' => 0,
        'rack_tiles' => json_encode([]),
    ]);

    expect(GamePlayer::where('user_id', $user->id)->count())->toBe(1);

    $this->actingAs($user, 'sanctum')
        ->deleteJson('/api/auth/user');

    expect(GamePlayer::where('user_id', $user->id)->count())->toBe(0);
});

it('returns 401 for unauthenticated request', function (): void {
    $response = $this->deleteJson('/api/auth/user');

    $response->assertStatus(401);
});

it('only deletes the authenticated user and not other users', function (): void {
    $userToDelete = User::factory()->create();
    $otherUser = User::factory()->create();

    $this->actingAs($userToDelete, 'sanctum')
        ->deleteJson('/api/auth/user');

    expect(User::find($userToDelete->id))->toBeNull();
    expect(User::find($otherUser->id))->not->toBeNull();
});
