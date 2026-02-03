<?php

use App\Domain\Game\Enums\MoveType;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\GamePlayer;
use App\Domain\Game\Models\Move;
use App\Domain\User\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->opponent = User::factory()->create();
    $this->game = Game::factory()->active()->create([
        'current_turn_user_id' => $this->user->id,
    ]);
    GamePlayer::factory()->create([
        'game_id' => $this->game->id,
        'user_id' => $this->user->id,
    ]);
    GamePlayer::factory()->create([
        'game_id' => $this->game->id,
        'user_id' => $this->opponent->id,
    ]);
});

it('returns move history for a game', function (): void {
    Sanctum::actingAs($this->user);

    $move1 = Move::factory()->create([
        'game_id' => $this->game->id,
        'user_id' => $this->user->id,
        'type' => MoveType::Play,
        'words' => ['TEST'],
        'score' => 10,
        'score_breakdown' => [
            'total' => 10,
            'words_total' => 10,
            'bonus_total' => 0,
            'words' => [['word' => 'TEST', 'baseScore' => 10, 'multipliedScore' => 10, 'multipliers' => []]],
            'bonuses' => [],
        ],
        'created_at' => now()->subMinutes(10),
    ]);

    $move2 = Move::factory()->create([
        'game_id' => $this->game->id,
        'user_id' => $this->opponent->id,
        'type' => MoveType::Play,
        'words' => ['HELLO'],
        'score' => 15,
        'score_breakdown' => [
            'total' => 15,
            'words_total' => 15,
            'bonus_total' => 0,
            'words' => [['word' => 'HELLO', 'baseScore' => 15, 'multipliedScore' => 15, 'multipliers' => []]],
            'bonuses' => [],
        ],
        'created_at' => now()->subMinutes(5),
    ]);

    $response = $this->getJson("/api/games/{$this->game->ulid}/moves");

    $response->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.ulid', $move2->ulid) // Most recent first
        ->assertJsonPath('data.0.type', 'play')
        ->assertJsonPath('data.0.user.ulid', $this->opponent->ulid)
        ->assertJsonPath('data.0.words', ['HELLO'])
        ->assertJsonPath('data.0.score', 15)
        ->assertJsonPath('data.0.score_breakdown.total', 15)
        ->assertJsonPath('data.1.ulid', $move1->ulid);
});

it('returns moves without score breakdown for older games', function (): void {
    Sanctum::actingAs($this->user);

    Move::factory()->create([
        'game_id' => $this->game->id,
        'user_id' => $this->user->id,
        'type' => MoveType::Play,
        'words' => ['OLD'],
        'score' => 5,
        'score_breakdown' => null, // No breakdown for old moves
    ]);

    $response = $this->getJson("/api/games/{$this->game->ulid}/moves");

    $response->assertOk()
        ->assertJsonPath('data.0.score_breakdown', null);
});

it('returns pass moves', function (): void {
    Sanctum::actingAs($this->user);

    Move::factory()->pass()->create([
        'game_id' => $this->game->id,
        'user_id' => $this->user->id,
    ]);

    $response = $this->getJson("/api/games/{$this->game->ulid}/moves");

    $response->assertOk()
        ->assertJsonPath('data.0.type', 'pass')
        ->assertJsonPath('data.0.score', 0);
});

it('returns swap moves with tiles count', function (): void {
    Sanctum::actingAs($this->user);

    Move::factory()->create([
        'game_id' => $this->game->id,
        'user_id' => $this->user->id,
        'type' => MoveType::Swap,
        'tiles' => [
            ['letter' => 'A', 'points' => 1],
            ['letter' => 'B', 'points' => 3],
            ['letter' => 'C', 'points' => 3],
        ],
        'words' => null,
        'score' => 0,
    ]);

    $response = $this->getJson("/api/games/{$this->game->ulid}/moves");

    $response->assertOk()
        ->assertJsonPath('data.0.type', 'swap')
        ->assertJsonPath('data.0.tiles_count', 3);
});

it('requires authentication', function (): void {
    $response = $this->getJson("/api/games/{$this->game->ulid}/moves");

    $response->assertUnauthorized();
});

it('requires the user to be a player in the game', function (): void {
    $outsider = User::factory()->create();
    Sanctum::actingAs($outsider);

    $response = $this->getJson("/api/games/{$this->game->ulid}/moves");

    $response->assertForbidden();
});
