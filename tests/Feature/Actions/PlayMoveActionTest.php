<?php

use App\Domain\Game\Actions\PlayMoveAction;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\GamePlayer;
use App\Domain\Support\Models\Dictionary;
use App\Domain\User\Models\User;

beforeEach(function (): void {
    $this->action = app(PlayMoveAction::class);
});

it('grants 25 point empty rack bonus when clearing rack with empty bag', function (): void {
    // Add the word to dictionary so it's valid
    Dictionary::create(['word' => 'CAT', 'language' => 'en']);

    $player = User::factory()->create();
    $opponent = User::factory()->create();

    // Create a game with empty tile bag
    $game = Game::factory()->create([
        'language' => 'en',
        'current_turn_user_id' => $player->id,
        'tile_bag' => [], // Empty bag
        'board_state' => createBoardWithTiles([
            // Existing word on board: "AT" starting at (7,7)
            ['letter' => 'A', 'x' => 7, 'y' => 7, 'points' => 1],
            ['letter' => 'T', 'x' => 8, 'y' => 7, 'points' => 1],
        ]),
    ]);

    // Player has exactly the tiles needed to form "CAT" by adding C
    $gamePlayer = GamePlayer::factory()->create([
        'game_id' => $game->id,
        'user_id' => $player->id,
        'turn_order' => 1,
        'score' => 50,
        'rack_tiles' => [
            ['letter' => 'C', 'points' => 3],
        ],
    ]);

    GamePlayer::factory()->create([
        'game_id' => $game->id,
        'user_id' => $opponent->id,
        'turn_order' => 2,
        'score' => 40,
        'rack_tiles' => [
            ['letter' => 'X', 'points' => 8],
        ],
    ]);

    $game = $game->fresh(['players', 'gamePlayers']);

    // Play the C to form "CAT" - this empties the rack
    $move = $this->action->execute($game, $player, [
        ['letter' => 'C', 'x' => 6, 'y' => 7, 'points' => 3, 'is_blank' => false],
    ]);

    $gamePlayer->refresh();

    // CAT = 3 + 1 + 1 = 5 points (on center row, no multipliers for C at position 6,7)
    // Plus 25 point empty rack bonus = 30 total for this move
    // Starting score 50 + 30 = 80
    expect($move->score)->toBe(30)
        ->and($gamePlayer->score)->toBe(80)
        ->and($gamePlayer->rack_tiles)->toBe([])
        ->and($gamePlayer->received_empty_rack_bonus)->toBeTrue();
});

it('does not grant empty rack bonus when bag has tiles', function (): void {
    Dictionary::create(['word' => 'CAT', 'language' => 'en']);

    $player = User::factory()->create();
    $opponent = User::factory()->create();

    // Create a game with tiles in the bag
    $game = Game::factory()->create([
        'language' => 'en',
        'current_turn_user_id' => $player->id,
        'tile_bag' => [
            ['letter' => 'Z', 'points' => 10],
        ],
        'board_state' => createBoardWithTiles([
            ['letter' => 'A', 'x' => 7, 'y' => 7, 'points' => 1],
            ['letter' => 'T', 'x' => 8, 'y' => 7, 'points' => 1],
        ]),
    ]);

    $gamePlayer = GamePlayer::factory()->create([
        'game_id' => $game->id,
        'user_id' => $player->id,
        'turn_order' => 1,
        'score' => 50,
        'rack_tiles' => [
            ['letter' => 'C', 'points' => 3],
        ],
    ]);

    GamePlayer::factory()->create([
        'game_id' => $game->id,
        'user_id' => $opponent->id,
        'turn_order' => 2,
        'score' => 40,
        'rack_tiles' => [
            ['letter' => 'X', 'points' => 8],
        ],
    ]);

    $game = $game->fresh(['players', 'gamePlayers']);

    $move = $this->action->execute($game, $player, [
        ['letter' => 'C', 'x' => 6, 'y' => 7, 'points' => 3, 'is_blank' => false],
    ]);

    $gamePlayer->refresh();

    // No bonus because bag had tiles - player drew the Z
    // CAT = 5 points only
    expect($move->score)->toBe(5)
        ->and($gamePlayer->score)->toBe(55)
        ->and($gamePlayer->rack_tiles)->not->toBe([])
        ->and($gamePlayer->received_empty_rack_bonus)->toBeFalse();
});

it('does not grant empty rack bonus when rack is not emptied', function (): void {
    Dictionary::create(['word' => 'CAT', 'language' => 'en']);

    $player = User::factory()->create();
    $opponent = User::factory()->create();

    $game = Game::factory()->create([
        'language' => 'en',
        'current_turn_user_id' => $player->id,
        'tile_bag' => [], // Empty bag
        'board_state' => createBoardWithTiles([
            ['letter' => 'A', 'x' => 7, 'y' => 7, 'points' => 1],
            ['letter' => 'T', 'x' => 8, 'y' => 7, 'points' => 1],
        ]),
    ]);

    // Player has more tiles than they will play
    $gamePlayer = GamePlayer::factory()->create([
        'game_id' => $game->id,
        'user_id' => $player->id,
        'turn_order' => 1,
        'score' => 50,
        'rack_tiles' => [
            ['letter' => 'C', 'points' => 3],
            ['letter' => 'E', 'points' => 1], // Extra tile
        ],
    ]);

    GamePlayer::factory()->create([
        'game_id' => $game->id,
        'user_id' => $opponent->id,
        'turn_order' => 2,
        'score' => 40,
        'rack_tiles' => [
            ['letter' => 'X', 'points' => 8],
        ],
    ]);

    $game = $game->fresh(['players', 'gamePlayers']);

    $move = $this->action->execute($game, $player, [
        ['letter' => 'C', 'x' => 6, 'y' => 7, 'points' => 3, 'is_blank' => false],
    ]);

    $gamePlayer->refresh();

    // No bonus because rack still has tiles
    expect($move->score)->toBe(5)
        ->and($gamePlayer->score)->toBe(55)
        ->and($gamePlayer->rack_tiles)->toHaveCount(1)
        ->and($gamePlayer->received_empty_rack_bonus)->toBeFalse();
});
