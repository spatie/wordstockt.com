<?php

declare(strict_types=1);

namespace App\Domain\Game\Support\Scoring;

use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\GamePlayer;
use App\Domain\Game\Support\Board;

readonly class ScoringContext
{
    /**
     * @param  array<int, array{word: string, tiles: array}>  $words
     * @param  array<int, array{letter: string, points: int, x: int, y: int, is_blank: bool}>  $placedTiles
     * @param  array<string, bool>  $placedPositions
     */
    public function __construct(
        public Game $game,
        public array $words,
        public array $placedTiles,
        public array $placedPositions,
        public Board $board,
        public ?GamePlayer $gamePlayer = null,
        public bool $isEndGame = false,
        public bool $playerClearedRack = false,
    ) {}

    public static function forMove(
        Game $game,
        array $words,
        array $placedTiles,
        Board $board,
    ): self {
        $placedPositions = collect($placedTiles)
            ->mapWithKeys(fn (array $tile): array => ["{$tile['x']},{$tile['y']}" => true])
            ->all();

        return new self(
            game: $game,
            words: $words,
            placedTiles: $placedTiles,
            placedPositions: $placedPositions,
            board: $board,
        );
    }

    public static function forEndGame(
        Game $game,
        GamePlayer $gamePlayer,
        bool $clearedRack,
    ): self {
        return new self(
            game: $game,
            words: [],
            placedTiles: [],
            placedPositions: [],
            board: app(Board::class),
            gamePlayer: $gamePlayer,
            isEndGame: true,
            playerClearedRack: $clearedRack,
        );
    }

    public function tileCount(): int
    {
        return count($this->placedTiles);
    }

    public function isPositionNewlyPlaced(int $x, int $y): bool
    {
        return isset($this->placedPositions["$x,$y"]);
    }
}
