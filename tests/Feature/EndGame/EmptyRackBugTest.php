<?php

use App\Domain\Game\Actions\PassAction;
use App\Domain\Game\Actions\PlayMoveAction;
use App\Domain\Game\Enums\GameStatus;
use App\Domain\Support\Models\Dictionary;
use App\Domain\User\Models\User;

it('ends game after opponent gets final turn when player empties rack with empty bag', function (): void {
    Dictionary::create(['word' => 'HOI', 'language' => 'nl']);
    Dictionary::create(['word' => 'JOA', 'language' => 'nl']);

    $player1 = User::factory()->create();
    $player2 = User::factory()->create();
    $game = createGameWithPlayers(player1: $player1, player2: $player2, language: 'nl');

    $gamePlayer1 = $game->gamePlayers()->where('user_id', $player1->id)->first();
    $gamePlayer1->update([
        'rack_tiles' => [
            ['letter' => 'H', 'points' => 4, 'is_blank' => false],
            ['letter' => 'O', 'points' => 1, 'is_blank' => false],
            ['letter' => 'I', 'points' => 4, 'is_blank' => false],
        ],
        'has_received_blank' => true,
    ]);

    $gamePlayer2 = $game->gamePlayers()->where('user_id', $player2->id)->first();
    $gamePlayer2->update([
        'rack_tiles' => [
            ['letter' => 'J', 'points' => 4, 'is_blank' => false],
            ['letter' => 'A', 'points' => 1, 'is_blank' => false],
        ],
        'has_received_blank' => true,
    ]);

    $game->update([
        'tile_bag' => [
            ['letter' => 'X', 'points' => 8, 'is_blank' => false],
            ['letter' => 'Y', 'points' => 4, 'is_blank' => false],
            ['letter' => 'Z', 'points' => 10, 'is_blank' => false],
        ],
    ]);

    // Player 1 plays "HOI" - draws 3 from bag, emptying it
    $tiles = [
        ['letter' => 'H', 'points' => 4, 'x' => 6, 'y' => 7, 'is_blank' => false],
        ['letter' => 'O', 'points' => 1, 'x' => 7, 'y' => 7, 'is_blank' => false],
        ['letter' => 'I', 'points' => 4, 'x' => 8, 'y' => 7, 'is_blank' => false],
    ];

    app(PlayMoveAction::class)->execute($game->fresh(), $player1, $tiles);

    $game->refresh();

    expect($game->tile_bag)->toBeEmpty();
    expect($game->current_turn_user_id)->toBe($player2->id);
    expect($gamePlayer1->fresh()->rack_tiles)->toHaveCount(3);

    // Player 2 plays "JOA" vertically - empties rack with empty bag
    $tiles2 = [
        ['letter' => 'J', 'points' => 4, 'x' => 7, 'y' => 6, 'is_blank' => false],
        ['letter' => 'A', 'points' => 1, 'x' => 7, 'y' => 8, 'is_blank' => false],
    ];

    app(PlayMoveAction::class)->execute($game->fresh(), $player2, $tiles2);

    $game->refresh();

    // Game should NOT end yet - Player 1 gets a final turn
    expect($game->status)->toBe(GameStatus::Active);
    expect($game->current_turn_user_id)->toBe($player1->id);

    // Player 1 takes their final turn (passes)
    app(PassAction::class)->execute($game->fresh(), $player1);

    $game->refresh();

    // NOW the game should be finished
    expect($game->status)->toBe(GameStatus::Finished)
        ->and($gamePlayer2->fresh()->rack_tiles)->toBeEmpty()
        ->and($game->tile_bag)->toBeEmpty();
});
