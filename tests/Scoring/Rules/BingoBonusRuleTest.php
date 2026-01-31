<?php

use App\Domain\Game\Support\Scoring\Rules\BingoBonusRule;
use App\Domain\Game\Support\Scoring\ScoringResult;

beforeEach(function (): void {
    $this->rule = new BingoBonusRule;
});

it('has correct identifier', function (): void {
    expect($this->rule->getIdentifier())->toBe('scoring.bingo_bonus');
});

it('has correct name', function (): void {
    expect($this->rule->getName())->toBe('Bingo Bonus');
});

it('is enabled by default', function (): void {
    expect($this->rule->isEnabled())->toBeTrue();
});

it('adds 50 points for 7 tiles', function (): void {
    $tiles = [];
    for ($i = 0; $i < 7; $i++) {
        $tiles[] = ['letter' => 'A', 'points' => 1, 'x' => $i, 'y' => 0, 'is_blank' => false];
    }

    $context = createScoringContext(
        words: [createWordData('AAAAAAA', $tiles)],
        placedTiles: $tiles,
    );

    $result = $this->rule->apply($context, ScoringResult::empty());

    expect($result->getBonusTotal())->toBe(50)
        ->and($result->hasBonus('scoring.bingo_bonus'))->toBeTrue();
});

it('does not add bonus for 6 tiles', function (): void {
    $tiles = [];
    for ($i = 0; $i < 6; $i++) {
        $tiles[] = ['letter' => 'A', 'points' => 1, 'x' => $i, 'y' => 0, 'is_blank' => false];
    }

    $context = createScoringContext(
        words: [createWordData('AAAAAA', $tiles)],
        placedTiles: $tiles,
    );

    $result = $this->rule->apply($context, ScoringResult::empty());

    expect($result->getBonusTotal())->toBe(0)
        ->and($result->hasBonus('scoring.bingo_bonus'))->toBeFalse();
});

it('does not add bonus for 1 tile', function (): void {
    $tiles = [
        ['letter' => 'A', 'points' => 1, 'x' => 0, 'y' => 0, 'is_blank' => false],
    ];

    $context = createScoringContext(
        words: [createWordData('A', $tiles)],
        placedTiles: $tiles,
    );

    $result = $this->rule->apply($context, ScoringResult::empty());

    expect($result->getBonusTotal())->toBe(0);
});

it('does not add bonus for empty tiles', function (): void {
    $context = createScoringContext(
        words: [],
        placedTiles: [],
    );

    $result = $this->rule->apply($context, ScoringResult::empty());

    expect($result->getBonusTotal())->toBe(0);
});

it('adds correct bonus description', function (): void {
    $tiles = [];
    for ($i = 0; $i < 7; $i++) {
        $tiles[] = ['letter' => 'A', 'points' => 1, 'x' => $i, 'y' => 0, 'is_blank' => false];
    }

    $context = createScoringContext(
        words: [createWordData('AAAAAAA', $tiles)],
        placedTiles: $tiles,
    );

    $result = $this->rule->apply($context, ScoringResult::empty());
    $bonuses = $result->getBonuses();

    expect($bonuses)->toHaveCount(1)
        ->and($bonuses->first()['description'])->toBe('Used all 7 tiles');
});
