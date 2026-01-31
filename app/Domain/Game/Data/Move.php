<?php

declare(strict_types=1);

namespace App\Domain\Game\Data;

use Illuminate\Support\Collection;

readonly class Move
{
    /**
     * @param  array<int, array{letter: string, points: int, x: int, y: int, is_blank: bool}>  $tiles
     */
    public function __construct(
        public array $tiles,
    ) {}

    /**
     * @param  array<int, array{letter: string, points: int, x: int, y: int, is_blank: bool}>  $tiles
     */
    public static function fromArray(array $tiles): self
    {
        return new self($tiles);
    }

    public function isEmpty(): bool
    {
        return count($this->tiles) === 0;
    }

    public function tileCount(): int
    {
        return count($this->tiles);
    }

    /**
     * @return Collection<int, array{letter: string, points: int, x: int, y: int, is_blank: bool}>
     */
    public function toCollection(): Collection
    {
        return collect($this->tiles);
    }

    /**
     * @return array<int, array{x: int, y: int}>
     */
    public function getPositions(): array
    {
        return array_map(
            fn (array $tile): array => ['x' => $tile['x'], 'y' => $tile['y']],
            $this->tiles
        );
    }

    /**
     * @return array<int, string>
     */
    public function getLetters(): array
    {
        return array_map(
            fn (array $tile): string => $tile['letter'],
            $this->tiles
        );
    }

    public function isHorizontal(): bool
    {
        if ($this->tileCount() <= 1) {
            return true;
        }

        $firstY = $this->tiles[0]['y'];

        return collect($this->tiles)->every(fn (array $tile): bool => $tile['y'] === $firstY);
    }

    public function isVertical(): bool
    {
        if ($this->tileCount() <= 1) {
            return true;
        }

        $firstX = $this->tiles[0]['x'];

        return collect($this->tiles)->every(fn (array $tile): bool => $tile['x'] === $firstX);
    }

    public function coversCenter(): bool
    {
        return collect($this->tiles)
            ->where('x', 7)
            ->where('y', 7)
            ->isNotEmpty();
    }
}
