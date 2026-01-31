<?php

declare(strict_types=1);

namespace App\Domain\Game\Data;

use Illuminate\Support\Collection;
use JsonSerializable;

readonly class ValidatedWord implements JsonSerializable
{
    /**
     * @param  Collection<int, array{x: int, y: int}>  $tiles
     */
    public function __construct(
        public string $word,
        public bool $valid,
        public Collection $tiles,
    ) {}

    public function containsTileAt(int $x, int $y): bool
    {
        return $this->tiles
            ->where('x', $x)
            ->where('y', $y)
            ->isNotEmpty();
    }

    public function jsonSerialize(): array
    {
        return [
            'word' => $this->word,
            'valid' => $this->valid,
            'tiles' => $this->tiles->all(),
        ];
    }
}
