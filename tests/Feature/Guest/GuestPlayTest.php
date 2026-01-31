<?php

use App\Domain\Game\Actions\PlayMoveAction;
use App\Domain\Game\Enums\GameStatus;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\GamePlayer;
use App\Domain\Support\Models\Dictionary;
use App\Domain\User\Models\User;
use App\Domain\User\Models\UserStatistics;

beforeEach(function (): void {
    Dictionary::create(['word' => 'CAT', 'language' => 'en']);
});

it('allows guest to play a move', function (): void {
    $guest = User::factory()->guest()->create();
    $opponent = User::factory()->create();

    $game = Game::factory()->create([
        'language' => 'en',
        'status' => GameStatus::Active,
        'current_turn_user_id' => $guest->id,
        'tile_bag' => [['letter' => 'Z', 'points' => 10]],
        'board_state' => createBoardWithTiles([
            ['letter' => 'A', 'x' => 7, 'y' => 7, 'points' => 1],
            ['letter' => 'T', 'x' => 8, 'y' => 7, 'points' => 1],
        ]),
    ]);

    GamePlayer::factory()->create([
        'game_id' => $game->id,
        'user_id' => $guest->id,
        'turn_order' => 1,
        'score' => 0,
        'rack_tiles' => [['letter' => 'C', 'points' => 3]],
    ]);

    GamePlayer::factory()->create([
        'game_id' => $game->id,
        'user_id' => $opponent->id,
        'turn_order' => 2,
        'score' => 0,
        'rack_tiles' => [['letter' => 'X', 'points' => 8]],
    ]);

    $game = $game->fresh(['players', 'gamePlayers']);

    $move = app(PlayMoveAction::class)->execute($game, $guest, [
        ['letter' => 'C', 'x' => 6, 'y' => 7, 'points' => 3, 'is_blank' => false],
    ]);

    expect($move->score)->toBe(5);
});

it('does not update statistics for guest moves', function (): void {
    $guest = User::factory()->guest()->create();
    $opponent = User::factory()->create();

    $game = Game::factory()->create([
        'language' => 'en',
        'status' => GameStatus::Active,
        'current_turn_user_id' => $guest->id,
        'tile_bag' => [['letter' => 'Z', 'points' => 10]],
        'board_state' => createBoardWithTiles([
            ['letter' => 'A', 'x' => 7, 'y' => 7, 'points' => 1],
            ['letter' => 'T', 'x' => 8, 'y' => 7, 'points' => 1],
        ]),
    ]);

    GamePlayer::factory()->create([
        'game_id' => $game->id,
        'user_id' => $guest->id,
        'turn_order' => 1,
        'score' => 0,
        'rack_tiles' => [['letter' => 'C', 'points' => 3]],
    ]);

    GamePlayer::factory()->create([
        'game_id' => $game->id,
        'user_id' => $opponent->id,
        'turn_order' => 2,
        'score' => 0,
        'rack_tiles' => [['letter' => 'X', 'points' => 8]],
    ]);

    $game = $game->fresh(['players', 'gamePlayers']);

    app(PlayMoveAction::class)->execute($game, $guest, [
        ['letter' => 'C', 'x' => 6, 'y' => 7, 'points' => 3, 'is_blank' => false],
    ]);

    expect(UserStatistics::where('user_id', $guest->id)->exists())->toBeFalse();
});

it('does not unlock achievements for guest moves', function (): void {
    $guest = User::factory()->guest()->create();
    $opponent = User::factory()->create();

    $game = Game::factory()->create([
        'language' => 'en',
        'status' => GameStatus::Active,
        'current_turn_user_id' => $guest->id,
        'tile_bag' => [['letter' => 'Z', 'points' => 10]],
        'board_state' => createBoardWithTiles([
            ['letter' => 'A', 'x' => 7, 'y' => 7, 'points' => 1],
            ['letter' => 'T', 'x' => 8, 'y' => 7, 'points' => 1],
        ]),
    ]);

    GamePlayer::factory()->create([
        'game_id' => $game->id,
        'user_id' => $guest->id,
        'turn_order' => 1,
        'score' => 0,
        'rack_tiles' => [['letter' => 'C', 'points' => 3]],
    ]);

    GamePlayer::factory()->create([
        'game_id' => $game->id,
        'user_id' => $opponent->id,
        'turn_order' => 2,
        'score' => 0,
        'rack_tiles' => [['letter' => 'X', 'points' => 8]],
    ]);

    $game = $game->fresh(['players', 'gamePlayers']);

    $move = app(PlayMoveAction::class)->execute($game, $guest, [
        ['letter' => 'C', 'x' => 6, 'y' => 7, 'points' => 3, 'is_blank' => false],
    ]);

    expect($move->relationLoaded('unlockedAchievements'))->toBeFalse();
});
