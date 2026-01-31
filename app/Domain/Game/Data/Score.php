<?php

declare(strict_types=1);

namespace App\Domain\Game\Data;

class Score
{
    /**
     * @param  array<int, array{word: string, score: int}>  $words
     */
    public function __construct(
        public int $total = 0,
        public int $bingoBonus = 0,
        public array $words = [],
    ) {}

    public static function empty(): self
    {
        return new self;
    }

    public function addWordScore(string $word, int $score): self
    {
        $this->words[] = ['word' => $word, 'score' => $score];
        $this->total += $score;

        return $this;
    }

    public function addBingoBonus(int $bonus = 50): self
    {
        $this->bingoBonus = $bonus;
        $this->total += $bonus;

        return $this;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function getWordsCount(): int
    {
        return count($this->words);
    }

    /**
     * @return array{total: int, bingo_bonus: int, words: array<int, array{word: string, score: int}>}
     */
    public function toArray(): array
    {
        return [
            'total' => $this->total,
            'bingo_bonus' => $this->bingoBonus,
            'words' => $this->words,
        ];
    }
}
