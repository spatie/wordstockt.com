<?php

use App\Domain\Game\Models\Game;
use App\Domain\Support\Models\Dictionary;
use App\Domain\User\Models\User;

function placeWord(Game $game, string $word, int $startX, int $startY, bool $horizontal = true): void
{
    $board = $game->board_state;

    foreach (str_split($word) as $i => $letter) {
        $x = $horizontal ? $startX + $i : $startX;
        $y = $horizontal ? $startY : $startY + $i;
        $board[$y][$x] = ['letter' => $letter, 'points' => 1, 'is_blank' => false];
    }

    $game->update(['board_state' => $board]);
}

function addToDictionary(string $word, string $language = 'en', array $attributes = []): void
{
    Dictionary::updateOrCreate(
        ['language' => $language, 'word' => $word],
        ['times_played' => 0, 'last_played_at' => now(), ...$attributes]
    );
}

it('returns 401 for unauthenticated request', function (): void {
    $game = createGameWithPlayers();

    $this->getJson("/api/games/{$game->ulid}/word-info?x=7&y=7")
        ->assertStatus(401);
});

it('returns 403 for non-player', function (): void {
    $user = User::factory()->create();
    $game = createGameWithPlayers();

    $this->actingAs($user, 'sanctum')
        ->getJson("/api/games/{$game->ulid}/word-info?x=7&y=7")
        ->assertStatus(403);
});

it('returns word info for tapped position', function (): void {
    $user = User::factory()->create();
    $game = createGameWithPlayers(player1: $user);

    placeWord($game, 'HI', 7, 7);
    addToDictionary('HI', 'en', ['times_played' => 5, 'definition' => '{"senses": [{"definition": "A greeting"}]}']);

    $this->actingAs($user, 'sanctum')
        ->getJson("/api/games/{$game->ulid}/word-info?x=7&y=7")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.word', 'HI')
        ->assertJsonPath('data.0.times_played', 5)
        ->assertJsonPath('data.0.definition.senses.0.definition', 'A greeting');
});

it('returns multiple words when tile is at intersection', function (): void {
    $user = User::factory()->create();
    $game = createGameWithPlayers(player1: $user);

    placeWord($game, 'HI', 7, 7, horizontal: true);
    placeWord($game, 'HE', 7, 7, horizontal: false);
    addToDictionary('HI', 'en', ['times_played' => 10]);
    addToDictionary('HE', 'en', ['times_played' => 20]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/games/{$game->ulid}/word-info?x=7&y=7");

    $response->assertOk()->assertJsonCount(2, 'data');

    $words = collect($response->json('data'))->pluck('word')->sort()->values();
    expect($words->all())->toBe(['HE', 'HI']);
});

it('hides definition when empty', function (?string $definition): void {
    $user = User::factory()->create();
    $game = createGameWithPlayers(player1: $user);

    placeWord($game, 'HI', 7, 7);
    addToDictionary('HI', 'en', ['definition' => $definition]);

    $this->actingAs($user, 'sanctum')
        ->getJson("/api/games/{$game->ulid}/word-info?x=7&y=7")
        ->assertOk()
        ->assertJsonMissing(['definition']);
})->with([null, '']);

it('returns empty when no tile at position', function (): void {
    $user = User::factory()->create();
    $game = createGameWithPlayers(player1: $user);

    $this->actingAs($user, 'sanctum')
        ->getJson("/api/games/{$game->ulid}/word-info?x=7&y=7")
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('returns empty for single letter', function (): void {
    $user = User::factory()->create();
    $game = createGameWithPlayers(player1: $user);

    placeWord($game, 'A', 7, 7);

    $this->actingAs($user, 'sanctum')
        ->getJson("/api/games/{$game->ulid}/word-info?x=7&y=7")
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('validates coordinates', function (string $query, string $error): void {
    $user = User::factory()->create();
    $game = createGameWithPlayers(player1: $user);

    $this->actingAs($user, 'sanctum')
        ->getJson("/api/games/{$game->ulid}/word-info?{$query}")
        ->assertStatus(422)
        ->assertJsonValidationErrors([$error]);
})->with([
    'x too high' => ['x=15&y=7', 'x'],
    'y negative' => ['x=7&y=-1', 'y'],
    'x missing' => ['y=7', 'x'],
    'y missing' => ['x=7', 'y'],
]);
