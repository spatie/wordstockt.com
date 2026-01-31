<?php

declare(strict_types=1);

namespace App\Domain\Game\Support\Scoring;

use Illuminate\Support\Collection;

readonly class ScoringResult
{
    /** @var Collection<int, array{word: string, baseScore: int, multipliedScore: int, multipliers: array}> */
    private Collection $wordScores;

    /** @var Collection<int, array{rule: string, points: int, description: string}> */
    private Collection $bonuses;

    public function __construct()
    {
        $this->wordScores = collect();
        $this->bonuses = collect();
    }

    public static function empty(): self
    {
        return new self;
    }

    public function addWordScore(
        string $word,
        int $baseScore,
        int $multipliedScore,
        array $multipliers = [],
    ): self {
        $this->wordScores->push([
            'word' => $word,
            'baseScore' => $baseScore,
            'multipliedScore' => $multipliedScore,
            'multipliers' => $multipliers,
        ]);

        return $this;
    }

    public function addBonus(string $ruleIdentifier, int $points, string $description): self
    {
        $this->bonuses->push([
            'rule' => $ruleIdentifier,
            'points' => $points,
            'description' => $description,
        ]);

        return $this;
    }

    public function getWordsTotal(): int
    {
        return $this->wordScores->sum('multipliedScore');
    }

    public function getBonusTotal(): int
    {
        return $this->bonuses->sum('points');
    }

    public function getTotal(): int
    {
        return $this->getWordsTotal() + $this->getBonusTotal();
    }

    /** @return Collection<int, array{word: string, baseScore: int, multipliedScore: int, multipliers: array}> */
    public function getWordScores(): Collection
    {
        return $this->wordScores;
    }

    /** @return Collection<int, array{rule: string, points: int, description: string}> */
    public function getBonuses(): Collection
    {
        return $this->bonuses;
    }

    public function hasBonus(string $ruleIdentifier): bool
    {
        return $this->bonuses->where('rule', $ruleIdentifier)->isNotEmpty();
    }

    /**
     * @return array{total: int, words_total: int, bonus_total: int, words: array, bonuses: array}
     */
    public function toArray(): array
    {
        return [
            'total' => $this->getTotal(),
            'words_total' => $this->getWordsTotal(),
            'bonus_total' => $this->getBonusTotal(),
            'words' => $this->wordScores->all(),
            'bonuses' => $this->bonuses->all(),
        ];
    }
}
