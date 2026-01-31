<?php

use App\Domain\Game\Actions\PlayMoveAction;
use App\Domain\Game\Enums\GameStatus;
use App\Domain\Support\Models\Dictionary;
use App\Domain\User\Models\User;

it('ends game when player empties rack with empty bag', function (): void {
    // Add Dutch words to dictionary
    Dictionary::create(['word' => 'HOI', 'language' => 'nl']);
    Dictionary::create(['word' => 'JOA', 'language' => 'nl']); // created by J-O-A vertically

    $player1 = User::factory()->create();
    $player2 = User::factory()->create();
    $game = createGameWithPlayers(player1: $player1, player2: $player2, language: 'nl');

    // Setup: Player 1 has 3 tiles, bag has 3 tiles (when player 1 plays, bag becomes empty)
    $gamePlayer1 = $game->gamePlayers()->where('user_id', $player1->id)->first();
    $gamePlayer1->update([
        'rack_tiles' => [
            ['letter' => 'H', 'points' => 4, 'is_blank' => false],
            ['letter' => 'O', 'points' => 1, 'is_blank' => false],
            ['letter' => 'I', 'points' => 4, 'is_blank' => false],
        ],
        'has_received_blank' => true, // Prevent blank swap which would leave tiles in bag
    ]);

    $gamePlayer2 = $game->gamePlayers()->where('user_id', $player2->id)->first();
    $gamePlayer2->update([
        'rack_tiles' => [
            ['letter' => 'J', 'points' => 4, 'is_blank' => false],
            ['letter' => 'A', 'points' => 1, 'is_blank' => false],
        ],
        'has_received_blank' => true, // Prevent blank swap
    ]);

    $game->update([
        'tile_bag' => [
            ['letter' => 'X', 'points' => 8, 'is_blank' => false],
            ['letter' => 'Y', 'points' => 4, 'is_blank' => false],
            ['letter' => 'Z', 'points' => 10, 'is_blank' => false],
        ],
    ]);

    // Player 1 plays "HOI" (valid Dutch word) horizontally through center - all 3 tiles (will draw 3 from bag, emptying it)
    $tiles = [
        ['letter' => 'H', 'points' => 4, 'x' => 6, 'y' => 7, 'is_blank' => false],
        ['letter' => 'O', 'points' => 1, 'x' => 7, 'y' => 7, 'is_blank' => false],
        ['letter' => 'I', 'points' => 4, 'x' => 8, 'y' => 7, 'is_blank' => false],
    ];

    app(PlayMoveAction::class)->execute($game->fresh(), $player1, $tiles);

    $game->refresh();

    // After Player 1's move:
    // - Player 1 should have 3 tiles in rack (drew from bag)
    // - Bag should be empty
    // - Turn should be Player 2's
    expect($game->tile_bag)->toBeEmpty();
    expect($game->current_turn_user_id)->toBe($player2->id);
    expect($gamePlayer1->fresh()->rack_tiles)->toHaveCount(3);

    // Now Player 2 plays vertically at x=7 to create "JOA" - all 2 tiles (bag is empty, so no refill)
    // This uses the existing "O" from "HOI" at (7,7)
    $tiles2 = [
        ['letter' => 'J', 'points' => 4, 'x' => 7, 'y' => 6, 'is_blank' => false],
        ['letter' => 'A', 'points' => 1, 'x' => 7, 'y' => 8, 'is_blank' => false],
    ];

    app(PlayMoveAction::class)->execute($game->fresh(), $player2, $tiles2);

    $game->refresh();

    // EXPECTED: Game should end because:
    // - Player 2 has empty rack
    // - Bag is empty
    // - Player 1 already had their final turn
    expect($game->status)->toBe(GameStatus::Finished)
        ->and($gamePlayer2->fresh()->rack_tiles)->toBeEmpty()
        ->and($game->tile_bag)->toBeEmpty();
});
