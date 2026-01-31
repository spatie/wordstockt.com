<?php

use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\GamePlayer;
use App\Domain\Game\Support\Rules\EndGame\EmptyRackRule;
use App\Domain\User\Models\User;

beforeEach(function (): void {
    $this->rule = new EmptyRackRule;
});

function createGameForEmptyRackTest(
    array $player1Rack = [],
    array $player2Rack = [],
    array $tileBag = [],
    int $currentTurnPlayer = 1,
): Game {
    $player1 = User::factory()->create();
    $player2 = User::factory()->create();

    $currentTurnUserId = $currentTurnPlayer === 1 ? $player1->id : $player2->id;

    $game = Game::factory()->create([
        'tile_bag' => $tileBag,
        'current_turn_user_id' => $currentTurnUserId,
    ]);

    GamePlayer::factory()->create([
        'game_id' => $game->id,
        'user_id' => $player1->id,
        'rack_tiles' => $player1Rack,
        'turn_order' => 1,
    ]);

    GamePlayer::factory()->create([
        'game_id' => $game->id,
        'user_id' => $player2->id,
        'rack_tiles' => $player2Rack,
        'turn_order' => 2,
    ]);

    return $game->fresh(['players', 'gamePlayers']);
}

function someTiles(): array
{
    return [
        ['letter' => 'A', 'points' => 1],
        ['letter' => 'B', 'points' => 3],
    ];
}

it('has correct identifier', function (): void {
    expect($this->rule->getIdentifier())->toBe('endgame.empty_rack');
});

it('has correct name', function (): void {
    expect($this->rule->getName())->toBe('Empty Rack');
});

it('is enabled by default', function (): void {
    expect($this->rule->isEnabled())->toBeTrue();
});

it('returns correct end reason', function (): void {
    expect($this->rule->getEndReason())
        ->toBe('A player emptied their rack with no tiles remaining in the bag.');
});

it('returns false when bag is not empty', function (): void {
    $game = createGameForEmptyRackTest(
        player1Rack: [],
        player2Rack: someTiles(),
        tileBag: [['letter' => 'A', 'points' => 1]],
        currentTurnPlayer: 2,
    );

    expect($this->rule->shouldEndGame($game))->toBeFalse();
});

it('returns false when bag is empty but all players have tiles', function (): void {
    $game = createGameForEmptyRackTest(
        player1Rack: someTiles(),
        player2Rack: someTiles(),
        tileBag: [],
    );

    expect($this->rule->shouldEndGame($game))->toBeFalse();
});

it('returns false when player just emptied rack and it is still their turn', function (): void {
    // Player 1 just played their last tiles, but it's still their turn
    // (before the turn switches). Opponent gets one more move.
    $game = createGameForEmptyRackTest(
        player1Rack: [],
        player2Rack: someTiles(),
        tileBag: [],
        currentTurnPlayer: 1, // Still player 1's turn
    );

    expect($this->rule->shouldEndGame($game))->toBeFalse();
});

it('returns true when player emptied rack and opponent had their final turn', function (): void {
    // Player 1 emptied their rack, turn switched to player 2,
    // player 2 made their move, now we check end game.
    $game = createGameForEmptyRackTest(
        player1Rack: [],
        player2Rack: someTiles(),
        tileBag: [],
        currentTurnPlayer: 2, // Player 2's turn (they just played)
    );

    expect($this->rule->shouldEndGame($game))->toBeTrue();
});

it('returns true when player 2 emptied rack and player 1 had their final turn', function (): void {
    // Same scenario but reversed - player 2 emptied rack first
    $game = createGameForEmptyRackTest(
        player1Rack: someTiles(),
        player2Rack: [],
        tileBag: [],
        currentTurnPlayer: 1, // Player 1's turn (they just played their final move)
    );

    expect($this->rule->shouldEndGame($game))->toBeTrue();
});
