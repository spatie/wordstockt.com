<?php

use App\Domain\Game\Actions\EndGameAction;
use App\Domain\Game\Enums\GameStatus;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\GamePlayer;
use App\Domain\User\Models\User;

beforeEach(function (): void {
    $this->action = new EndGameAction;
});

/*
|--------------------------------------------------------------------------
| Helper Functions
|--------------------------------------------------------------------------
*/

function createEndGameScenario(
    int $player1Score = 100,
    int $player2Score = 80,
    array $player1Rack = [],
    array $player2Rack = [],
): object {
    $player1 = User::factory()->create();
    $player2 = User::factory()->create();

    $game = Game::factory()->create([
        'status' => GameStatus::Active,
        'tile_bag' => [],
        'current_turn_user_id' => $player1->id,
    ]);

    $gamePlayer1 = GamePlayer::factory()->create([
        'game_id' => $game->id,
        'user_id' => $player1->id,
        'score' => $player1Score,
        'rack_tiles' => $player1Rack,
        'turn_order' => 1,
    ]);

    $gamePlayer2 = GamePlayer::factory()->create([
        'game_id' => $game->id,
        'user_id' => $player2->id,
        'score' => $player2Score,
        'rack_tiles' => $player2Rack,
        'turn_order' => 2,
    ]);

    return (object) [
        'game' => $game->fresh(['players', 'gamePlayers']),
        'player1' => $player1,
        'player2' => $player2,
        'gamePlayer1' => $gamePlayer1,
        'gamePlayer2' => $gamePlayer2,
    ];
}

function tile(string $letter, int $points): array
{
    return ['letter' => $letter, 'points' => $points, 'is_blank' => false];
}

function tiles(string ...$letters): array
{
    $pointValues = [
        'A' => 1, 'B' => 3, 'C' => 3, 'D' => 2, 'E' => 1, 'F' => 4, 'G' => 2,
        'H' => 4, 'I' => 1, 'J' => 8, 'K' => 5, 'L' => 1, 'M' => 3, 'N' => 1,
        'O' => 1, 'P' => 3, 'Q' => 10, 'R' => 1, 'S' => 1, 'T' => 1, 'U' => 1,
        'V' => 4, 'W' => 4, 'X' => 8, 'Y' => 4, 'Z' => 10,
    ];

    return array_map(
        fn (string $letter): array => tile($letter, $pointValues[$letter] ?? 1),
        $letters
    );
}

/*
|--------------------------------------------------------------------------
| Game Status Tests
|--------------------------------------------------------------------------
*/

it('sets game status to finished', function (): void {
    $scenario = createEndGameScenario();

    $this->action->execute($scenario->game);

    expect($scenario->game->fresh()->status)->toBe(GameStatus::Finished);
});

/*
|--------------------------------------------------------------------------
| Winner Determination Tests
|--------------------------------------------------------------------------
*/

it('sets winner to player with highest score', function (): void {
    $scenario = createEndGameScenario(
        player1Score: 150,
        player2Score: 120,
    );

    $this->action->execute($scenario->game);

    expect($scenario->game->fresh()->winner_id)->toBe($scenario->player1->id);
});

it('determines winner after penalties are applied', function (): void {
    // Player 1 starts higher but has expensive tiles remaining
    // Player 2 has empty rack (bonus was already granted during their move)
    $scenario = createEndGameScenario(
        player1Score: 100,
        player2Score: 95,
        player1Rack: tiles('Q', 'Z'), // 10 + 10 = 20 point penalty
        player2Rack: [],              // 0 penalty (bonus already in score)
    );

    $this->action->execute($scenario->game);

    // Player 1: 100 - 20 = 80
    // Player 2: 95 - 0 = 95
    expect($scenario->game->fresh()->winner_id)->toBe($scenario->player2->id);
});

/*
|--------------------------------------------------------------------------
| End Game Penalty Tests
|--------------------------------------------------------------------------
*/

it('deducts remaining tile values from each player score', function (): void {
    $scenario = createEndGameScenario(
        player1Score: 100,
        player2Score: 80,
        player1Rack: tiles('A', 'E'),     // 1 + 1 = 2 points
        player2Rack: tiles('Q', 'Z', 'X'), // 10 + 10 + 8 = 28 points
    );

    $this->action->execute($scenario->game);

    expect($scenario->gamePlayer1->fresh()->score)->toBe(98)  // 100 - 2
        ->and($scenario->gamePlayer2->fresh()->score)->toBe(52); // 80 - 28
});

it('applies no penalty when rack is empty', function (): void {
    // Player 1 cleared their rack (bonus was already granted during their move)
    $scenario = createEndGameScenario(
        player1Score: 100,
        player2Score: 80,
        player1Rack: [],
        player2Rack: tiles('A', 'B'),
    );

    $this->action->execute($scenario->game);

    // Player 1: no penalty, no additional bonus (already received during move)
    expect($scenario->gamePlayer1->fresh()->score)->toBe(100);
});

/*
|--------------------------------------------------------------------------
| End Game Penalty Tests (Bonus is granted during move, not here)
|--------------------------------------------------------------------------
*/

it('does not give additional bonus at end game - bonus was granted during move', function (): void {
    // The +25 empty rack bonus is now granted immediately in PlayMoveAction
    // when the player clears their rack. EndGameAction only applies penalties.
    $scenario = createEndGameScenario(
        player1Score: 100,  // Assume bonus already included if applicable
        player2Score: 80,
        player1Rack: [],               // Empty rack
        player2Rack: tiles('A', 'B'),  // Has remaining tiles (4 point penalty)
    );

    $this->action->execute($scenario->game);

    // No additional bonus granted - only penalties applied
    expect($scenario->gamePlayer1->fresh()->score)->toBe(100)
        ->and($scenario->gamePlayer2->fresh()->score)->toBe(76); // 80 - 4
});

it('applies penalties when both players have remaining tiles', function (): void {
    $scenario = createEndGameScenario(
        player1Score: 100,
        player2Score: 80,
        player1Rack: tiles('A'),
        player2Rack: tiles('B'),
    );

    $this->action->execute($scenario->game);

    expect($scenario->gamePlayer1->fresh()->score)->toBe(99)  // 100 - 1
        ->and($scenario->gamePlayer2->fresh()->score)->toBe(77); // 80 - 3
});

/*
|--------------------------------------------------------------------------
| Player Stats Tests
|--------------------------------------------------------------------------
*/

it('increments games played for both players', function (): void {
    $scenario = createEndGameScenario();

    $this->action->execute($scenario->game);

    expect($scenario->player1->fresh()->games_played)->toBe(1)
        ->and($scenario->player2->fresh()->games_played)->toBe(1);
});

it('increments games won only for the winner', function (): void {
    $scenario = createEndGameScenario(
        player1Score: 150,
        player2Score: 100,
    );

    $this->action->execute($scenario->game);

    expect($scenario->player1->fresh()->games_won)->toBe(1)
        ->and($scenario->player2->fresh()->games_won)->toBe(0);
});

/*
|--------------------------------------------------------------------------
| Edge Cases
|--------------------------------------------------------------------------
*/

it('handles tie by selecting first player with highest score', function (): void {
    $scenario = createEndGameScenario(
        player1Score: 100,
        player2Score: 100,
        player1Rack: [],
        player2Rack: [],
    );

    $this->action->execute($scenario->game);

    // Both have empty racks, no penalties, scores stay at 100
    // Winner should be one of them (implementation uses sortByDesc->first)
    expect($scenario->game->fresh()->winner_id)->toBeIn([
        $scenario->player1->id,
        $scenario->player2->id,
    ]);
});

it('handles game where both players have empty racks', function (): void {
    // Both players cleared their racks - bonuses were granted during their moves
    // EndGameAction only applies penalties (none in this case)
    $scenario = createEndGameScenario(
        player1Score: 100,
        player2Score: 80,
        player1Rack: [],
        player2Rack: [],
    );

    $this->action->execute($scenario->game);

    // No penalties, no additional bonuses - scores unchanged
    expect($scenario->gamePlayer1->fresh()->score)->toBe(100)
        ->and($scenario->gamePlayer2->fresh()->score)->toBe(80);
});

it('correctly calculates complex end game scenario', function (): void {
    // Realistic end game: player 1 cleared rack (bonus already in their score)
    // Player 2 has tiles left and will get penalty
    $scenario = createEndGameScenario(
        player1Score: 152,                  // 127 base + 25 bonus (already granted)
        player2Score: 118,
        player1Rack: [],                    // Cleared rack
        player2Rack: tiles('E', 'R', 'T'),  // 1 + 1 + 1 = 3 point penalty
    );

    $this->action->execute($scenario->game);

    $game = $scenario->game->fresh();

    expect($scenario->gamePlayer1->fresh()->score)->toBe(152)  // No change
        ->and($scenario->gamePlayer2->fresh()->score)->toBe(115) // 118 - 3
        ->and($game->status)->toBe(GameStatus::Finished)
        ->and($game->winner_id)->toBe($scenario->player1->id);
});

it('prevents scores from going below zero', function (): void {
    // Game ended via consecutive passes with no moves played
    // Both players have expensive tiles remaining
    $scenario = createEndGameScenario(
        player1Score: 0,
        player2Score: 0,
        player1Rack: tiles('Q', 'Z', 'X'),  // 10 + 10 + 8 = 28 penalty
        player2Rack: tiles('J', 'K'),        // 8 + 5 = 13 penalty
    );

    $this->action->execute($scenario->game);

    expect($scenario->gamePlayer1->fresh()->score)->toBe(0)
        ->and($scenario->gamePlayer2->fresh()->score)->toBe(0);
});
