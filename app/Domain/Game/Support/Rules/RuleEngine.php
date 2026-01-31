<?php

declare(strict_types=1);

namespace App\Domain\Game\Support\Rules;

use App\Domain\Game\Data\Move;
use App\Domain\Game\Enums\GameAction;
use App\Domain\Game\Exceptions\InvalidMoveException;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Support\Rules\EndGame\EndGameRule;
use App\Domain\Game\Support\Rules\Game\GameRule;
use App\Domain\Game\Support\Rules\Turn\TurnRule;
use App\Domain\User\Models\User;
use Illuminate\Support\Collection;

readonly class RuleEngine
{
    /** @var Collection<int, TurnRule> */
    private Collection $turnRules;

    /** @var Collection<int, GameRule> */
    private Collection $gameRules;

    /** @var Collection<int, EndGameRule> */
    private Collection $endGameRules;

    public function __construct()
    {
        $this->turnRules = collect();
        $this->gameRules = collect();
        $this->endGameRules = collect();
    }

    public function addTurnRule(TurnRule $rule): self
    {
        $this->turnRules->push($rule);

        return $this;
    }

    public function addGameRule(GameRule $rule): self
    {
        $this->gameRules->push($rule);

        return $this;
    }

    public function addEndGameRule(EndGameRule $rule): self
    {
        $this->endGameRules->push($rule);

        return $this;
    }

    /**
     * @return Collection<int, RuleResult>
     */
    public function validateMove(Game $game, Move $move, array $board): Collection
    {
        return $this->turnRules
            ->filter(fn (TurnRule $rule): bool => $rule->isEnabled())
            ->map(fn (TurnRule $rule): \App\Domain\Game\Support\Rules\RuleResult => $rule->validate($game, $move, $board))
            ->filter(fn (RuleResult $result): bool => $result->failed())
            ->values();
    }

    /**
     * @return Collection<int, RuleResult>
     */
    public function validateAction(Game $game, User $user, GameAction $action): Collection
    {
        return $this->gameRules
            ->filter(fn (GameRule $rule): bool => $rule->isEnabled())
            ->map(fn (GameRule $rule): \App\Domain\Game\Support\Rules\RuleResult => $rule->isActionAllowed($game, $user, $action))
            ->filter(fn (RuleResult $result): bool => $result->failed())
            ->values();
    }

    /**
     * @throws InvalidMoveException
     */
    public function validateActionOrFail(Game $game, User $user, GameAction $action): void
    {
        $failure = $this->validateAction($game, $user, $action)->first();

        if (! $failure) {
            return;
        }

        throw new InvalidMoveException($failure->message);
    }

    public function checkEndGame(Game $game): ?EndGameRule
    {
        return $this->endGameRules
            ->filter(fn (EndGameRule $rule): bool => $rule->isEnabled())
            ->first(fn (EndGameRule $rule): bool => $rule->shouldEndGame($game));
    }

    /** @return Collection<int, TurnRule> */
    public function getTurnRules(): Collection
    {
        return $this->turnRules;
    }

    /** @return Collection<int, GameRule> */
    public function getGameRules(): Collection
    {
        return $this->gameRules;
    }

    /** @return Collection<int, EndGameRule> */
    public function getEndGameRules(): Collection
    {
        return $this->endGameRules;
    }
}
