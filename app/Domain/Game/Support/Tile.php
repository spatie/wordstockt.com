<?php

namespace App\Domain\Game\Support;

class Tile
{
    public function __construct(
        public readonly string $letter,
        public readonly int $points,
        public readonly bool $isBlank = false,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            letter: $data['letter'],
            points: $data['points'],
            isBlank: $data['is_blank'] ?? false,
        );
    }

    public static function blank(): self
    {
        return new self(letter: '*', points: 0, isBlank: true);
    }

    public function toArray(): array
    {
        return [
            'letter' => $this->letter,
            'points' => $this->points,
            'is_blank' => $this->isBlank,
        ];
    }

    public function withLetter(string $letter): self
    {
        return new self(
            letter: $letter,
            points: $this->points,
            isBlank: $this->isBlank,
        );
    }
}
