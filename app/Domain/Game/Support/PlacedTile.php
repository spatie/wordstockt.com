<?php

namespace App\Domain\Game\Support;

class PlacedTile extends Tile
{
    public function __construct(
        string $letter,
        int $points,
        bool $isBlank,
        public readonly int $x,
        public readonly int $y,
    ) {
        parent::__construct($letter, $points, $isBlank);
    }

    #[\Override]
    public static function fromArray(array $data): self
    {
        return new self(
            letter: $data['letter'],
            points: $data['points'],
            isBlank: $data['is_blank'] ?? false,
            x: $data['x'],
            y: $data['y'],
        );
    }

    public static function fromTile(Tile $tile, int $x, int $y): self
    {
        return new self(
            letter: $tile->letter,
            points: $tile->points,
            isBlank: $tile->isBlank,
            x: $x,
            y: $y,
        );
    }

    #[\Override]
    public function toArray(): array
    {
        return [
            'letter' => $this->letter,
            'points' => $this->points,
            'is_blank' => $this->isBlank,
            'x' => $this->x,
            'y' => $this->y,
        ];
    }

    public function toTile(): Tile
    {
        return new Tile($this->letter, $this->points, $this->isBlank);
    }

    public function positionKey(): string
    {
        return "{$this->x},{$this->y}";
    }
}
