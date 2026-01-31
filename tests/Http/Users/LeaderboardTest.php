<?php

use App\Domain\Game\Models\Game;
use App\Domain\User\Models\User;
use Carbon\Carbon;

it('returns top players ordered by elo', function (): void {
    $user = User::factory()->create(['elo_rating' => 1000]);

    User::factory()->create([
        'username' => 'player1',
        'elo_rating' => 1800,
    ]);
    User::factory()->create([
        'username' => 'player2',
        'elo_rating' => 1600,
    ]);
    User::factory()->create([
        'username' => 'player3',
        'elo_rating' => 1700,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/users/leaderboard');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => ['rank', 'username', 'eloRating'],
            ],
            'meta' => ['type', 'label', 'currentUser'],
        ]);

    $leaderboard = $response->json('data');
    expect($leaderboard[0]['username'])->toBe('player1')
        ->and($leaderboard[0]['rank'])->toBe(1)
        ->and($leaderboard[1]['username'])->toBe('player3')
        ->and($leaderboard[1]['rank'])->toBe(2)
        ->and($leaderboard[2]['username'])->toBe('player2')
        ->and($leaderboard[2]['rank'])->toBe(3);
});

it('limits to 50 players', function (): void {
    $user = User::factory()->create(['elo_rating' => 1000]);

    for ($i = 0; $i < 60; $i++) {
        User::factory()->create([
            'elo_rating' => 1000 + $i,
        ]);
    }

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/users/leaderboard');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(50);
});

it('includes games stats', function (): void {
    $user = User::factory()->create(['elo_rating' => 1000]);
    User::factory()->create([
        'games_played' => 20,
        'games_won' => 15,
        'elo_rating' => 1500,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/users/leaderboard');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => ['gamesPlayed', 'gamesWon'],
            ],
        ]);
});

it('returns 401 for unauthenticated request', function (): void {
    $response = $this->getJson('/api/users/leaderboard');

    $response->assertStatus(401);
});

it('returns meta with type and label for elo leaderboard', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/users/leaderboard?type=elo');

    $response->assertOk()
        ->assertJsonPath('meta.type', 'elo')
        ->assertJsonPath('meta.label', 'ELO Rating');
});

it('returns monthly wins leaderboard', function (): void {
    $user = User::factory()->create();

    $player1 = User::factory()->create(['username' => 'monthly_winner']);
    $player2 = User::factory()->create(['username' => 'less_wins']);

    Game::factory()->finished()->count(5)->create([
        'winner_id' => $player1->id,
        'updated_at' => Carbon::now()->subDays(15),
    ]);

    Game::factory()->finished()->count(2)->create([
        'winner_id' => $player2->id,
        'updated_at' => Carbon::now()->subDays(10),
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/users/leaderboard?type=monthly');

    $response->assertOk()
        ->assertJsonPath('meta.type', 'monthly')
        ->assertJsonPath('meta.label', 'Monthly Wins');

    $leaderboard = $response->json('data');
    expect($leaderboard[0]['username'])->toBe('monthly_winner')
        ->and($leaderboard[0]['winsInPeriod'])->toBe(5)
        ->and($leaderboard[1]['username'])->toBe('less_wins')
        ->and($leaderboard[1]['winsInPeriod'])->toBe(2);
});

it('returns yearly wins leaderboard', function (): void {
    $user = User::factory()->create();

    $player1 = User::factory()->create(['username' => 'yearly_winner']);
    $player2 = User::factory()->create(['username' => 'runner_up']);

    Game::factory()->finished()->count(10)->create([
        'winner_id' => $player1->id,
        'updated_at' => Carbon::now()->subDays(100),
    ]);

    Game::factory()->finished()->count(3)->create([
        'winner_id' => $player2->id,
        'updated_at' => Carbon::now()->subDays(200),
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/users/leaderboard?type=yearly');

    $response->assertOk()
        ->assertJsonPath('meta.type', 'yearly')
        ->assertJsonPath('meta.label', 'Yearly Wins');

    $leaderboard = $response->json('data');
    expect($leaderboard[0]['username'])->toBe('yearly_winner')
        ->and($leaderboard[0]['winsInPeriod'])->toBe(10)
        ->and($leaderboard[1]['username'])->toBe('runner_up')
        ->and($leaderboard[1]['winsInPeriod'])->toBe(3);
});

it('excludes wins outside monthly period', function (): void {
    $user = User::factory()->create();

    $player = User::factory()->create(['username' => 'old_winner']);

    Game::factory()->finished()->count(5)->create([
        'winner_id' => $player->id,
        'updated_at' => Carbon::now()->subDays(35),
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/users/leaderboard?type=monthly');

    $response->assertOk();
    expect($response->json('data'))->toBeEmpty();
});

it('excludes wins outside yearly period', function (): void {
    $user = User::factory()->create();

    $player = User::factory()->create(['username' => 'ancient_winner']);

    Game::factory()->finished()->count(5)->create([
        'winner_id' => $player->id,
        'updated_at' => Carbon::now()->subDays(400),
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/users/leaderboard?type=yearly');

    $response->assertOk();
    expect($response->json('data'))->toBeEmpty();
});

it('uses rolling periods not calendar boundaries', function (): void {
    Carbon::setTestNow(Carbon::create(2026, 1, 15, 12, 0, 0));

    $user = User::factory()->create();
    $player = User::factory()->create(['username' => 'recent_winner']);

    Game::factory()->finished()->count(3)->create([
        'winner_id' => $player->id,
        'updated_at' => Carbon::now()->subDays(25),
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/users/leaderboard?type=monthly');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1)
        ->and($response->json('data.0.winsInPeriod'))->toBe(3);

    Carbon::setTestNow();
});

it('returns current user rank when not in top 50 for elo', function (): void {
    $user = User::factory()->create(['elo_rating' => 800]);

    for ($i = 0; $i < 55; $i++) {
        User::factory()->create(['elo_rating' => 1200 + $i]);
    }

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/users/leaderboard?type=elo');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(50);

    $currentUser = $response->json('meta.currentUser');
    expect($currentUser)->not->toBeNull()
        ->and($currentUser['rank'])->toBe(56)
        ->and($currentUser['ulid'])->toBe($user->ulid);
});

it('returns current user rank when not in top 50 for monthly', function (): void {
    $user = User::factory()->create();

    Game::factory()->finished()->count(1)->create([
        'winner_id' => $user->id,
        'updated_at' => Carbon::now()->subDays(5),
    ]);

    for ($i = 0; $i < 55; $i++) {
        $player = User::factory()->create();
        Game::factory()->finished()->count(10)->create([
            'winner_id' => $player->id,
            'updated_at' => Carbon::now()->subDays(5),
        ]);
    }

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/users/leaderboard?type=monthly');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(50);

    $currentUser = $response->json('meta.currentUser');
    expect($currentUser)->not->toBeNull()
        ->and($currentUser['rank'])->toBe(56)
        ->and($currentUser['winsInPeriod'])->toBe(1);
});

it('returns null for current user when in top 50', function (): void {
    $user = User::factory()->create(['elo_rating' => 2000]);

    User::factory()->create(['elo_rating' => 1500]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/users/leaderboard?type=elo');

    $response->assertOk();
    expect($response->json('meta.currentUser'))->toBeNull();
});

it('returns message when user has no wins in period', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/users/leaderboard?type=monthly');

    $response->assertOk();

    $currentUser = $response->json('meta.currentUser');
    expect($currentUser)->not->toBeNull()
        ->and($currentUser['rank'])->toBeNull()
        ->and($currentUser['message'])->toBe('Win at least 1 game this period to appear')
        ->and($currentUser['winsInPeriod'])->toBe(0);
});

it('breaks ties by elo rating for time-based leaderboards', function (): void {
    $user = User::factory()->create();

    $player1 = User::factory()->create([
        'username' => 'low_elo',
        'elo_rating' => 1000,
    ]);
    $player2 = User::factory()->create([
        'username' => 'high_elo',
        'elo_rating' => 1500,
    ]);

    Game::factory()->finished()->count(5)->create([
        'winner_id' => $player1->id,
        'updated_at' => Carbon::now()->subDays(5),
    ]);

    Game::factory()->finished()->count(5)->create([
        'winner_id' => $player2->id,
        'updated_at' => Carbon::now()->subDays(5),
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/users/leaderboard?type=monthly');

    $response->assertOk();

    $leaderboard = $response->json('data');
    expect($leaderboard[0]['username'])->toBe('high_elo')
        ->and($leaderboard[1]['username'])->toBe('low_elo');
});

it('returns empty array when no qualifying users for time-based leaderboard', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/users/leaderboard?type=monthly');

    $response->assertOk();
    expect($response->json('data'))->toBeEmpty();
});

it('returns validation error for invalid type', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/users/leaderboard?type=invalid');

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['type']);
});

it('defaults to elo type when no type specified', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/users/leaderboard');

    $response->assertOk()
        ->assertJsonPath('meta.type', 'elo');
});

it('only counts finished games for time-based leaderboards', function (): void {
    $user = User::factory()->create();

    $player = User::factory()->create(['username' => 'active_player']);

    Game::factory()->finished()->count(2)->create([
        'winner_id' => $player->id,
        'updated_at' => Carbon::now()->subDays(5),
    ]);

    Game::factory()->active()->count(3)->create([
        'winner_id' => $player->id,
        'updated_at' => Carbon::now()->subDays(5),
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/users/leaderboard?type=monthly');

    $response->assertOk();
    expect($response->json('data.0.winsInPeriod'))->toBe(2);
});

it('does not include winsInPeriod for elo leaderboard', function (): void {
    $user = User::factory()->create(['elo_rating' => 1500]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/users/leaderboard?type=elo');

    $response->assertOk();

    $entry = $response->json('data.0');
    expect($entry)->not->toHaveKey('winsInPeriod');
});
