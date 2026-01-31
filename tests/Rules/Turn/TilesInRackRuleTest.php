<?php

use App\Domain\Game\Support\Rules\Turn\TilesInRackRule;

beforeEach(function (): void {
    $this->rule = new TilesInRackRule;
});

it('has correct identifier', function (): void {
    expect($this->rule->getIdentifier())->toBe('turn.tiles_in_rack');
});

it('passes when all tiles are in player rack', function (): void {
    $game = createGameWithPlayers();
    $gamePlayer = $game->gamePlayers()->where('user_id', $game->current_turn_user_id)->first();
    $gamePlayer->update([
        'rack_tiles' => [
            ['letter' => 'A', 'points' => 1],
            ['letter' => 'B', 'points' => 3],
        ],
    ]);

    $board = createEmptyBoard();
    $move = createMove([
        ['letter' => 'A', 'x' => 7, 'y' => 7, 'points' => 1],
    ]);

    $result = $this->rule->validate($game, $move, $board);

    expect($result->passed)->toBeTrue();
});

it('passes when multiple tiles match in rack', function (): void {
    $game = createGameWithPlayers();
    $gamePlayer = $game->gamePlayers()->where('user_id', $game->current_turn_user_id)->first();
    $gamePlayer->update([
        'rack_tiles' => [
            ['letter' => 'C', 'points' => 3],
            ['letter' => 'A', 'points' => 1],
            ['letter' => 'T', 'points' => 1],
        ],
    ]);

    $board = createEmptyBoard();
    $move = createMove([
        ['letter' => 'C', 'x' => 6, 'y' => 7, 'points' => 3],
        ['letter' => 'A', 'x' => 7, 'y' => 7, 'points' => 1],
        ['letter' => 'T', 'x' => 8, 'y' => 7, 'points' => 1],
    ]);

    $result = $this->rule->validate($game, $move, $board);

    expect($result->passed)->toBeTrue();
});

it('fails when tile is not in rack', function (): void {
    $game = createGameWithPlayers();
    $gamePlayer = $game->gamePlayers()->where('user_id', $game->current_turn_user_id)->first();
    $gamePlayer->update([
        'rack_tiles' => [
            ['letter' => 'X', 'points' => 8],
        ],
    ]);

    $board = createEmptyBoard();
    $move = createMove([
        ['letter' => 'A', 'x' => 7, 'y' => 7, 'points' => 1],
    ]);

    $result = $this->rule->validate($game, $move, $board);

    expect($result->passed)->toBeFalse()
        ->and($result->message)->toContain("don't have the tile");
});

it('fails when trying to use same tile twice', function (): void {
    $game = createGameWithPlayers();
    $gamePlayer = $game->gamePlayers()->where('user_id', $game->current_turn_user_id)->first();
    $gamePlayer->update([
        'rack_tiles' => [
            ['letter' => 'A', 'points' => 1],
        ],
    ]);

    $board = createEmptyBoard();
    $move = createMove([
        ['letter' => 'A', 'x' => 7, 'y' => 7, 'points' => 1],
        ['letter' => 'A', 'x' => 8, 'y' => 7, 'points' => 1],
    ]);

    $result = $this->rule->validate($game, $move, $board);

    expect($result->passed)->toBeFalse();
});

it('handles blank tiles correctly', function (): void {
    $game = createGameWithPlayers();
    $gamePlayer = $game->gamePlayers()->where('user_id', $game->current_turn_user_id)->first();
    $gamePlayer->update([
        'rack_tiles' => [
            ['letter' => '*', 'points' => 0, 'is_blank' => true],
        ],
    ]);

    $board = createEmptyBoard();
    $move = createMove([
        ['letter' => 'A', 'x' => 7, 'y' => 7, 'points' => 0, 'is_blank' => true],
    ]);

    $result = $this->rule->validate($game, $move, $board);

    expect($result->passed)->toBeTrue();
});
