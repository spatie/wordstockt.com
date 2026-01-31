<?php

declare(strict_types=1);

namespace App\Domain\Game\Support\Scoring;

use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\GamePlayer;
use App\Domain\Game\Support\Board;
use App\Domain\Game\Support\Scoring\Rules\ScoringRule;
use Illuminate\Support\Collection;

readonly class ScoringEngine
{
    /** @var Collection<int, ScoringRule> */
    private Collection $rules;

    public function __construct()
    {
        $this->rules = collect();
    }

    public function addRule(ScoringRule $rule): self
    {
        $this->rules->push($rule);

        return $this;
    }

    public function calculateMoveScore(
        Game $game,
        array $words,
        array $placedTiles,
        Board $board,
    ): ScoringResult {
        $context = ScoringContext::forMove($game, $words, $placedTiles, $board);

        return $this->applyRules($context);
    }

    public function calculateEndGameScore(
        Game $game,
        GamePlayer $gamePlayer,
        bool $clearedRack,
    ): ScoringResult {
        $context = ScoringContext::forEndGame($game, $gamePlayer, $clearedRack);

        return $this->applyRules($context);
    }

    public function calculateEndGamePenalty(array $rackTiles): int
    {
        return collect($rackTiles)->sum(fn (array $tile) => $tile['points'] ?? 0);
    }

    private function applyRules(ScoringContext $context): ScoringResult
    {
        return $this->rules
            ->filter(fn (ScoringRule $rule): bool => $rule->isEnabled())
            ->reduce(
                fn (ScoringResult $result, ScoringRule $rule): \App\Domain\Game\Support\Scoring\ScoringResult => $rule->apply($context, $result),
                ScoringResult::empty()
            );
    }

    /** @return Collection<int, ScoringRule> */
    public function getRules(): Collection
    {
        return $this->rules;
    }
}
