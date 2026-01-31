<?php

namespace App\Domain\Game\Support;

use App\Domain\Game\Enums\SquareType;
use Illuminate\Support\Collection;

class Board
{
    public const BOARD_SIZE = 15;

    public const CENTER = 7;

    private const array SQUARE_TYPES = [
        '0,0' => SquareType::TripleWord, '0,7' => SquareType::TripleWord, '0,14' => SquareType::TripleWord,
        '7,0' => SquareType::TripleWord, '7,14' => SquareType::TripleWord,
        '14,0' => SquareType::TripleWord, '14,7' => SquareType::TripleWord, '14,14' => SquareType::TripleWord,

        '1,1' => SquareType::DoubleWord, '2,2' => SquareType::DoubleWord, '3,3' => SquareType::DoubleWord, '4,4' => SquareType::DoubleWord,
        '1,13' => SquareType::DoubleWord, '2,12' => SquareType::DoubleWord, '3,11' => SquareType::DoubleWord, '4,10' => SquareType::DoubleWord,
        '13,1' => SquareType::DoubleWord, '12,2' => SquareType::DoubleWord, '11,3' => SquareType::DoubleWord, '10,4' => SquareType::DoubleWord,
        '13,13' => SquareType::DoubleWord, '12,12' => SquareType::DoubleWord, '11,11' => SquareType::DoubleWord, '10,10' => SquareType::DoubleWord,
        '7,7' => SquareType::DoubleWord,

        '1,5' => SquareType::TripleLetter, '1,9' => SquareType::TripleLetter,
        '5,1' => SquareType::TripleLetter, '5,5' => SquareType::TripleLetter, '5,9' => SquareType::TripleLetter, '5,13' => SquareType::TripleLetter,
        '9,1' => SquareType::TripleLetter, '9,5' => SquareType::TripleLetter, '9,9' => SquareType::TripleLetter, '9,13' => SquareType::TripleLetter,
        '13,5' => SquareType::TripleLetter, '13,9' => SquareType::TripleLetter,

        '0,3' => SquareType::DoubleLetter, '0,11' => SquareType::DoubleLetter,
        '2,6' => SquareType::DoubleLetter, '2,8' => SquareType::DoubleLetter,
        '3,0' => SquareType::DoubleLetter, '3,7' => SquareType::DoubleLetter, '3,14' => SquareType::DoubleLetter,
        '6,2' => SquareType::DoubleLetter, '6,6' => SquareType::DoubleLetter, '6,8' => SquareType::DoubleLetter, '6,12' => SquareType::DoubleLetter,
        '7,3' => SquareType::DoubleLetter, '7,11' => SquareType::DoubleLetter,
        '8,2' => SquareType::DoubleLetter, '8,6' => SquareType::DoubleLetter, '8,8' => SquareType::DoubleLetter, '8,12' => SquareType::DoubleLetter,
        '11,0' => SquareType::DoubleLetter, '11,7' => SquareType::DoubleLetter, '11,14' => SquareType::DoubleLetter,
        '12,6' => SquareType::DoubleLetter, '12,8' => SquareType::DoubleLetter,
        '14,3' => SquareType::DoubleLetter, '14,11' => SquareType::DoubleLetter,
    ];

    public function createEmptyBoard(): array
    {
        return collect(range(0, self::BOARD_SIZE - 1))
            ->mapWithKeys(fn ($y): array => [$y => array_fill(0, self::BOARD_SIZE, null)])
            ->all();
    }

    public function getSquareType(int $x, int $y, ?array $boardTemplate = null): ?SquareType
    {
        if ($boardTemplate !== null) {
            $value = $boardTemplate[$y][$x] ?? null;

            if (! $value || $value === 'STAR') {
                return null;
            }

            return SquareType::tryFrom($value);
        }

        return self::SQUARE_TYPES["$y,$x"] ?? null;
    }

    public function isCenter(int $x, int $y): bool
    {
        if ($x !== self::CENTER) {
            return false;
        }

        return $y === self::CENTER;
    }

    public function isWithinBounds(int $x, int $y): bool
    {
        if ($x < 0) {
            return false;
        }

        if ($x >= self::BOARD_SIZE) {
            return false;
        }

        if ($y < 0) {
            return false;
        }

        return $y < self::BOARD_SIZE;
    }

    public function isCellEmpty(array $board, int $x, int $y): bool
    {
        return $board[$y][$x] === null;
    }

    public function placeTiles(array $board, array $tiles): array
    {
        collect($tiles)->each(function (array $tile) use (&$board): void {
            $board[$tile['y']][$tile['x']] = [
                'letter' => $tile['letter'],
                'points' => $tile['points'],
                'is_blank' => $tile['is_blank'] ?? false,
            ];
        });

        return $board;
    }

    public function findFormedWords(array $board, array $placedTiles): array
    {
        if ($placedTiles === []) {
            return [];
        }

        $placedTiles = collect($placedTiles);
        $isHorizontal = $this->isHorizontalPlacement($placedTiles);

        $mainWord = $this->getWordAt($board, $placedTiles->first()['x'], $placedTiles->first()['y'], $isHorizontal);
        $words = $mainWord ? collect([$mainWord]) : collect();

        $perpendicularWords = $placedTiles
            ->map(fn ($tile): ?array => $this->getWordAt($board, $tile['x'], $tile['y'], ! $isHorizontal))
            ->filter()
            ->filter(fn (array $word): bool => ! $this->isDuplicateWord($words, $word));

        return $words->merge($perpendicularWords)->all();
    }

    private function isHorizontalPlacement(Collection $tiles): bool
    {
        if ($tiles->count() === 1) {
            return true;
        }

        return $tiles->pluck('x')->unique()->count() > 1;
    }

    private function isDuplicateWord(Collection $existingWords, array $word): bool
    {
        return $existingWords
            ->where('word', $word['word'])
            ->where('start_x', $word['start_x'])
            ->where('start_y', $word['start_y'])
            ->where('horizontal', $word['horizontal'])
            ->isNotEmpty();
    }

    private function getWordAt(array $board, int $startX, int $startY, bool $horizontal): ?array
    {
        [$x, $y] = $this->findWordStart($board, $startX, $startY, $horizontal);

        $wordData = $this->collectWordTiles($board, $x, $y, $horizontal);

        if (strlen((string) $wordData['word']) < 2) {
            return null;
        }

        return [
            'word' => $wordData['word'],
            'tiles' => $wordData['tiles'],
            'start_x' => $x,
            'start_y' => $y,
            'horizontal' => $horizontal,
        ];
    }

    private function findWordStart(array $board, int $x, int $y, bool $horizontal): array
    {
        while (true) {
            $prevX = $horizontal ? $x - 1 : $x;
            $prevY = $horizontal ? $y : $y - 1;

            if (! $this->isWithinBounds($prevX, $prevY)) {
                break;
            }

            if ($this->isCellEmpty($board, $prevX, $prevY)) {
                break;
            }

            $x = $prevX;
            $y = $prevY;
        }

        return [$x, $y];
    }

    private function collectWordTiles(array $board, int $x, int $y, bool $horizontal): array
    {
        $word = '';
        $tiles = [];

        while ($this->hasTileAt($board, $x, $y)) {
            $tile = $board[$y][$x];
            $word .= $tile['letter'];
            $tiles[] = [
                'x' => $x,
                'y' => $y,
                'letter' => $tile['letter'],
                'points' => $tile['points'],
                'is_blank' => $tile['is_blank'] ?? false,
            ];

            $x += $horizontal ? 1 : 0;
            $y += $horizontal ? 0 : 1;
        }

        return ['word' => $word, 'tiles' => $tiles];
    }

    private function hasTileAt(array $board, int $x, int $y): bool
    {
        if (! $this->isWithinBounds($x, $y)) {
            return false;
        }

        return ! $this->isCellEmpty($board, $x, $y);
    }

    public function getBoardTemplate(): array
    {
        return collect(range(0, self::BOARD_SIZE - 1))
            ->mapWithKeys(fn ($y): array => [
                $y => collect(range(0, self::BOARD_SIZE - 1))
                    ->map(fn (int $x): ?string => $this->getCellType($x, $y))
                    ->all(),
            ])
            ->all();
    }

    private function getCellType(int $x, int $y): ?string
    {
        if ($this->isCenter($x, $y)) {
            return 'STAR';
        }

        return $this->getSquareType($x, $y)?->value;
    }
}
