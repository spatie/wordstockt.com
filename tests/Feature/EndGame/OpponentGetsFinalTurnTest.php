<?php

use App\Domain\Game\Actions\PassAction;
use App\Domain\Game\Actions\PlayMoveAction;
use App\Domain\Game\Enums\GameStatus;
use App\Domain\Support\Models\Dictionary;
use App\Domain\User\Models\User;

it('does not end game immediately when player empties rack - opponent gets a final turn', function (): void {
    Dictionary::create(['word' => 'HOI', 'language' => 'nl']);

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

    // Bag is already empty
    $game->update(['tile_bag' => []]);

    // Player 1 plays all 3 tiles - rack becomes empty with empty bag
    $tiles = [
        ['letter' => 'H', 'points' => 4, 'x' => 6, 'y' => 7, 'is_blank' => false],
        ['letter' => 'O', 'points' => 1, 'x' => 7, 'y' => 7, 'is_blank' => false],
        ['letter' => 'I', 'points' => 4, 'x' => 8, 'y' => 7, 'is_blank' => false],
    ];

    app(PlayMoveAction::class)->execute($game->fresh(), $player1, $tiles);

    $game->refresh();

    // Game should NOT be finished yet - opponent gets a final turn
    expect($game->status)->toBe(GameStatus::Active);
    expect($game->current_turn_user_id)->toBe($player2->id);
    expect($gamePlayer1->fresh()->rack_tiles)->toBeEmpty();
});

it('ends game after opponent takes their final turn following empty rack', function (): void {
    Dictionary::create(['word' => 'HOI', 'language' => 'nl']);

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

    $game->update(['tile_bag' => []]);

    // Player 1 empties their rack
    $tiles = [
        ['letter' => 'H', 'points' => 4, 'x' => 6, 'y' => 7, 'is_blank' => false],
        ['letter' => 'O', 'points' => 1, 'x' => 7, 'y' => 7, 'is_blank' => false],
        ['letter' => 'I', 'points' => 4, 'x' => 8, 'y' => 7, 'is_blank' => false],
    ];

    app(PlayMoveAction::class)->execute($game->fresh(), $player1, $tiles);

    // Player 2 passes (their final turn)
    app(PassAction::class)->execute($game->fresh(), $player2);

    $game->refresh();

    // NOW the game should be finished
    expect($game->status)->toBe(GameStatus::Finished);
});
