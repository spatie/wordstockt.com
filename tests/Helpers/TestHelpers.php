<?php

use App\Domain\Game\Data\Move;
use App\Domain\Game\Enums\GameStatus;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\GamePlayer;
use App\Domain\Game\Support\Board;
use App\Domain\User\Models\User;

if (function_exists('createEmptyBoard')) {
    return;
}

/**
 * Create an empty 15x15 board.
 */
function createEmptyBoard(): array
{
    $board = [];
    for ($y = 0; $y < Board::BOARD_SIZE; $y++) {
        $board[$y] = array_fill(0, Board::BOARD_SIZE, null);
    }

    return $board;
}

/**
 * Create a board with tiles placed.
 *
 * @param  array<int, array{letter: string, x: int, y: int, points: int, is_blank?: bool}>  $tiles
 */
function createBoardWithTiles(array $tiles): array
{
    $board = createEmptyBoard();
    foreach ($tiles as $tile) {
        $board[$tile['y']][$tile['x']] = [
            'letter' => $tile['letter'],
            'points' => $tile['points'],
            'is_blank' => $tile['is_blank'] ?? false,
        ];
    }

    return $board;
}

/**
 * Create a MoveData instance from tiles array.
 *
 * @param  array<int, array{letter: string, x: int, y: int, points?: int, is_blank?: bool}>  $tiles
 */
function createMove(array $tiles): Move
{
    return Move::fromArray(array_map(fn (array $tile): array => [
        'letter' => $tile['letter'],
        'points' => $tile['points'] ?? 1,
        'x' => $tile['x'],
        'y' => $tile['y'],
        'is_blank' => $tile['is_blank'] ?? false,
    ], $tiles));
}

/**
 * Create a game with two players for testing.
 */
function createGameWithPlayers(
    ?User $player1 = null,
    ?User $player2 = null,
    GameStatus $status = GameStatus::Active,
    ?string $language = 'en'
): Game {
    $player1 ??= User::factory()->create();
    $player2 ??= User::factory()->create();

    $game = Game::factory()->create([
        'status' => $status,
        'language' => $language,
        'current_turn_user_id' => $player1->id,
        'board_state' => createEmptyBoard(),
        'tile_bag' => createDefaultTileBag(),
    ]);

    GamePlayer::factory()->create([
        'game_id' => $game->id,
        'user_id' => $player1->id,
        'turn_order' => 1,
        'rack_tiles' => createDefaultRack(),
        'score' => 0,
    ]);

    GamePlayer::factory()->create([
        'game_id' => $game->id,
        'user_id' => $player2->id,
        'turn_order' => 2,
        'rack_tiles' => createDefaultRack(),
        'score' => 0,
    ]);

    return $game->fresh(['players', 'gamePlayers']);
}

/**
 * Create a default rack of tiles.
 */
function createDefaultRack(): array
{
    return [
        ['letter' => 'A', 'points' => 1],
        ['letter' => 'B', 'points' => 3],
        ['letter' => 'C', 'points' => 3],
        ['letter' => 'D', 'points' => 2],
        ['letter' => 'E', 'points' => 1],
        ['letter' => 'F', 'points' => 4],
        ['letter' => 'G', 'points' => 2],
    ];
}

/**
 * Create a default tile bag for testing.
 */
function createDefaultTileBag(): array
{
    $tiles = [];
    $distribution = [
        'A' => ['count' => 9, 'points' => 1],
        'B' => ['count' => 2, 'points' => 3],
        'C' => ['count' => 2, 'points' => 3],
        'D' => ['count' => 4, 'points' => 2],
        'E' => ['count' => 12, 'points' => 1],
        'F' => ['count' => 2, 'points' => 4],
        'G' => ['count' => 3, 'points' => 2],
        'H' => ['count' => 2, 'points' => 4],
        'I' => ['count' => 9, 'points' => 1],
        'J' => ['count' => 1, 'points' => 8],
        'K' => ['count' => 1, 'points' => 5],
        'L' => ['count' => 4, 'points' => 1],
        'M' => ['count' => 2, 'points' => 3],
        'N' => ['count' => 6, 'points' => 1],
        'O' => ['count' => 8, 'points' => 1],
        'P' => ['count' => 2, 'points' => 3],
        'Q' => ['count' => 1, 'points' => 10],
        'R' => ['count' => 6, 'points' => 1],
        'S' => ['count' => 4, 'points' => 1],
        'T' => ['count' => 6, 'points' => 1],
        'U' => ['count' => 4, 'points' => 1],
        'V' => ['count' => 2, 'points' => 4],
        'W' => ['count' => 2, 'points' => 4],
        'X' => ['count' => 1, 'points' => 8],
        'Y' => ['count' => 2, 'points' => 4],
        'Z' => ['count' => 1, 'points' => 10],
    ];

    foreach ($distribution as $letter => $info) {
        for ($i = 0; $i < $info['count']; $i++) {
            $tiles[] = ['letter' => $letter, 'points' => $info['points']];
        }
    }

    shuffle($tiles);

    return $tiles;
}

/**
 * Create a ScoringContext for testing move scoring.
 *
 * @param  array<int, array{word: string, tiles: array}>  $words
 * @param  array<int, array{letter: string, points: int, x: int, y: int, is_blank?: bool}>  $placedTiles
 */
function createScoringContext(
    array $words = [],
    array $placedTiles = [],
    ?Game $game = null,
): \App\Domain\Game\Support\Scoring\ScoringContext {
    if (! $game instanceof \App\Domain\Game\Models\Game) {
        $game = Mockery::mock(Game::class);
        $game->shouldReceive('getAttribute')->with('board_template')->andReturn(null);
    }

    return \App\Domain\Game\Support\Scoring\ScoringContext::forMove(
        game: $game,
        words: $words,
        placedTiles: $placedTiles,
        board: new \App\Domain\Game\Support\Board,
    );
}

/**
 * Create a ScoringContext with a single word for testing.
 */
function createScoringContextWithWord(string $word, int $startX = 7, int $startY = 7): \App\Domain\Game\Support\Scoring\ScoringContext
{
    $tiles = collect(str_split($word))
        ->map(fn (string $letter, int $index): array => [
            'x' => $startX + $index,
            'y' => $startY,
            'letter' => $letter,
            'points' => 1,
            'is_blank' => false,
        ])
        ->all();

    return createScoringContext(
        words: [[
            'word' => $word,
            'tiles' => $tiles,
        ]],
        placedTiles: $tiles,
    );
}

/**
 * Create word data for scoring tests.
 *
 * @param  array<int, array{letter: string, points: int, x: int, y: int, is_blank?: bool}>  $tiles
 */
function createWordData(string $word, array $tiles): array
{
    return [
        'word' => $word,
        'tiles' => array_map(fn (array $tile): array => [
            'letter' => $tile['letter'],
            'points' => $tile['points'],
            'x' => $tile['x'],
            'y' => $tile['y'],
            'is_blank' => $tile['is_blank'] ?? false,
        ], $tiles),
    ];
}

/**
 * Create a ScoringContext for testing word extension scenarios.
 *
 * @param  string  $existingWord  The word already on the board
 * @param  string  $extension  The letters being added to extend the word
 * @param  bool  $extendAtEnd  If true, extension is added at end; if false, at beginning
 */
function createExtendedWordContext(
    string $existingWord,
    string $extension,
    bool $extendAtEnd = true,
    int $startX = 7,
    int $startY = 7,
): \App\Domain\Game\Support\Scoring\ScoringContext {
    $existingTiles = [];
    $newTiles = [];
    $allTiles = [];

    if ($extendAtEnd) {
        // Existing word first, then extension
        foreach (str_split($existingWord) as $i => $letter) {
            $tile = [
                'x' => $startX + $i,
                'y' => $startY,
                'letter' => $letter,
                'points' => 1,
                'is_blank' => false,
            ];
            $existingTiles[] = $tile;
            $allTiles[] = $tile;
        }
        foreach (str_split($extension) as $i => $letter) {
            $tile = [
                'x' => $startX + strlen($existingWord) + $i,
                'y' => $startY,
                'letter' => $letter,
                'points' => 1,
                'is_blank' => false,
            ];
            $newTiles[] = $tile;
            $allTiles[] = $tile;
        }
    } else {
        // Extension first, then existing word
        foreach (str_split($extension) as $i => $letter) {
            $tile = [
                'x' => $startX + $i,
                'y' => $startY,
                'letter' => $letter,
                'points' => 1,
                'is_blank' => false,
            ];
            $newTiles[] = $tile;
            $allTiles[] = $tile;
        }
        foreach (str_split($existingWord) as $i => $letter) {
            $tile = [
                'x' => $startX + strlen($extension) + $i,
                'y' => $startY,
                'letter' => $letter,
                'points' => 1,
                'is_blank' => false,
            ];
            $existingTiles[] = $tile;
            $allTiles[] = $tile;
        }
    }

    $fullWord = $extendAtEnd ? $existingWord.$extension : $extension.$existingWord;

    return createScoringContext(
        words: [createWordData($fullWord, $allTiles)],
        placedTiles: $newTiles,
    );
}
