<?php

use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\GamePlayer;
use App\Domain\User\Models\User;
use Illuminate\Testing\TestResponse;

beforeEach(function (): void {
    config()->set('game.min_multiplayer_app_version', '1.7.0');
});

function createGameRequest(array $payload, array $headers = []): TestResponse
{
    return test()->actingAs(User::factory()->create(), 'sanctum')
        ->withHeaders($headers)
        ->postJson('/api/games', $payload);
}

it('blocks creating a 3-4 player game without an app version header (old app)', function (): void {
    createGameRequest(['language' => 'nl', 'max_players' => 3])
        ->assertStatus(422)
        ->assertJsonValidationErrors('max_players');
});

it('blocks creating a 3-4 player game from an app version below the minimum', function (): void {
    createGameRequest(['language' => 'nl', 'max_players' => 3], ['X-App-Version' => '1.6.0'])
        ->assertStatus(422);
});

it('allows creating a 3-4 player game from a supported app version', function (): void {
    createGameRequest(['language' => 'nl', 'max_players' => 4], ['X-App-Version' => '1.7.0'])
        ->assertStatus(201);
});

it('still lets old apps create two-player games', function (): void {
    createGameRequest(['language' => 'nl', 'max_players' => 2])->assertStatus(201);
    createGameRequest(['language' => 'nl'])->assertStatus(201); // defaults to 2
});

it('hides 3-4 player public games from old apps but shows two-player ones', function (): void {
    $host = User::factory()->create();
    $twoPlayer = Game::factory()->pending()->create(['is_public' => true, 'max_players' => 2]);
    GamePlayer::factory()->for($twoPlayer)->create(['user_id' => $host->id, 'turn_order' => 1]);
    $fourPlayer = Game::factory()->pending()->create(['is_public' => true, 'max_players' => 4]);
    GamePlayer::factory()->for($fourPlayer)->create(['user_id' => $host->id, 'turn_order' => 1]);

    $viewer = User::factory()->create();

    $oldApp = test()->actingAs($viewer, 'sanctum')->getJson('/api/games/public');
    $ulids = collect($oldApp->json('data'))->pluck('ulid');
    expect($ulids)->toContain($twoPlayer->ulid)
        ->and($ulids)->not->toContain($fourPlayer->ulid);

    $newApp = test()->actingAs($viewer, 'sanctum')
        ->withHeaders(['X-App-Version' => '1.7.0'])
        ->getJson('/api/games/public');
    $newUlids = collect($newApp->json('data'))->pluck('ulid');
    expect($newUlids)->toContain($twoPlayer->ulid)
        ->and($newUlids)->toContain($fourPlayer->ulid);
});
