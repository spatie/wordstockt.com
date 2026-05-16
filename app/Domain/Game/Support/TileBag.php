<?php

namespace App\Domain\Game\Support;

use App\Domain\Game\Support\TileDistributions\DutchDistribution;
use App\Domain\Game\Support\TileDistributions\EnglishDistribution;
use App\Domain\Game\Support\TileDistributions\TileDistribution;

class TileBag
{
    private const array DISTRIBUTIONS = [
        'nl' => DutchDistribution::class,
        'en' => EnglishDistribution::class,
    ];

    /** @param array<int, Tile> $tiles */
    private function __construct(private array $tiles) {}

    public static function forLanguage(string $language): self
    {
        $distribution = self::getDistribution($language);
        $tiles = $distribution->tiles();

        shuffle($tiles);

        return new self($tiles);
    }

    private static function getDistribution(string $language): TileDistribution
    {
        $class = self::DISTRIBUTIONS[$language] ?? self::DISTRIBUTIONS['nl'];

        return new $class;
    }

    /** @param array<int, array{letter: string, points: int, is_blank?: bool}> $tiles */
    public static function fromArray(array $tiles): self
    {
        return new self(
            array_map(Tile::fromArray(...), $tiles)
        );
    }

    /** @return array<int, Tile> */
    public function draw(int $count): array
    {
        $count = min($count, count($this->tiles));
        $drawn = [];

        for ($i = 0; $i < $count; $i++) {
            $drawn[] = array_shift($this->tiles);
        }

        return $drawn;
    }

    private const array VOWELS = ['A', 'E', 'I', 'O', 'U'];

    /**
     * @param  array<int, Tile>  $keptTiles
     * @return array<int, Tile>
     */
    public function drawForRack(array $keptTiles, int $count): array
    {
        $drawn = $this->draw($count);

        if (! $this->rackIsLopsided([...$keptTiles, ...$drawn])) {
            return $drawn;
        }

        $this->returnTiles($drawn);

        return $this->draw($count);
    }

    /** @param array<int, Tile> $rack */
    private function rackIsLopsided(array $rack): bool
    {
        if ($rack === []) {
            return false;
        }

        if ($this->rackContainsBlank($rack)) {
            return false;
        }

        $hasVowel = collect($rack)->contains(fn (Tile $tile): bool => $this->isVowel($tile));
        $hasConsonant = collect($rack)->contains(fn (Tile $tile): bool => ! $this->isVowel($tile));

        return ! ($hasVowel && $hasConsonant);
    }

    /** @param array<int, Tile> $rack */
    private function rackContainsBlank(array $rack): bool
    {
        return collect($rack)->contains(fn (Tile $tile): bool => $tile->isBlank);
    }

    private function isVowel(Tile $tile): bool
    {
        return in_array($tile->letter, self::VOWELS, true);
    }

    /** @return array<int, array{letter: string, points: int, is_blank: bool}> */
    public function drawAsArrays(int $count): array
    {
        return array_map(fn (Tile $tile): array => $tile->toArray(), $this->draw($count));
    }

    /** @param array<int, Tile|array{letter: string, points: int, is_blank?: bool}> $tiles */
    public function returnTiles(array $tiles): self
    {
        foreach ($tiles as $tile) {
            $this->tiles[] = $tile instanceof Tile ? $tile : Tile::fromArray($tile);
        }

        shuffle($this->tiles);

        return $this;
    }

    public function count(): int
    {
        return count($this->tiles);
    }

    public function isEmpty(): bool
    {
        return $this->tiles === [];
    }

    /** @param array<int, Tile> $tiles */
    public function swapOneForBlank(array $tiles): array
    {
        $tileToReturn = array_pop($tiles);
        $this->returnTiles([$tileToReturn]);
        $tiles[] = Tile::blank();

        return $tiles;
    }

    /** @param array<int, Tile> $tiles */
    public static function tilesToArray(array $tiles): array
    {
        return array_map(fn (Tile $tile): array => $tile->toArray(), $tiles);
    }

    /** @return array<int, array{letter: string, points: int, is_blank: bool}> */
    public function toArray(): array
    {
        return array_map(fn (Tile $tile): array => $tile->toArray(), $this->tiles);
    }
}
