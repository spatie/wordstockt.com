<?php

declare(strict_types=1);

namespace App\Domain\Game\Data;

use JsonSerializable;

readonly class TileStatus implements JsonSerializable
{
    public function __construct(
        public int $x,
        public int $y,
        public bool $valid,
    ) {}

    public function jsonSerialize(): array
    {
        return [
            'x' => $this->x,
            'y' => $this->y,
            'valid' => $this->valid,
        ];
    }
}
