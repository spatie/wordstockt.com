<?php

use App\Domain\User\Support\EloCalculator\EloCalculator;
use App\Domain\User\Support\EloCalculator\EloResult;

beforeEach(function (): void {
    $this->calculator = new EloCalculator;
});

it('returns an EloResult instance', function (): void {
    $result = $this->calculator->calculate(1200, 1200);

    expect($result)->toBeInstanceOf(EloResult::class);
});

it('calculates equal ratings correctly', function (): void {
    $result = $this->calculator->calculate(1200, 1200);

    expect($result->winnerNewElo)->toBe(1216);
    expect($result->winnerChange)->toBe(16);
    expect($result->loserNewElo)->toBe(1184);
    expect($result->loserChange)->toBe(-16);
});

it('gives fewer points when higher rated wins', function (): void {
    $result = $this->calculator->calculate(1400, 1200);

    expect($result->winnerChange)->toBeLessThan(16);
    expect($result->loserChange)->toBeGreaterThan(-16);
});

it('gives more points when lower rated wins', function (): void {
    $result = $this->calculator->calculate(1200, 1400);

    expect($result->winnerChange)->toBeGreaterThan(16);
    expect($result->loserChange)->toBeLessThan(-16);
});

it('maintains zero-sum elo changes', function (): void {
    $result = $this->calculator->calculate(1300, 1250);

    expect($result->winnerChange + $result->loserChange)->toBe(0);
});

it('handles large rating differences', function (): void {
    $result = $this->calculator->calculate(1000, 1600);

    expect($result->winnerNewElo)->toBeGreaterThan(1000);
    expect($result->loserNewElo)->toBeLessThan(1600);
    expect($result->winnerChange)->toBeGreaterThan(25);
});

it('never produces negative elo ratings', function (): void {
    $result = $this->calculator->calculate(100, 2000);

    expect($result->loserNewElo)->toBeGreaterThanOrEqual(0);
});

it('uses custom k-factor when provided', function (): void {
    $defaultCalculator = new EloCalculator;
    $highKCalculator = new EloCalculator(kFactor: 64);

    $defaultResult = $defaultCalculator->calculate(1200, 1200);
    $highKResult = $highKCalculator->calculate(1200, 1200);

    expect($highKResult->winnerChange)->toBe($defaultResult->winnerChange * 2);
});

it('reads k-factor from config by default', function (): void {
    config(['game.elo.k_factor' => 16]);

    $calculator = new EloCalculator;
    $result = $calculator->calculate(1200, 1200);

    expect($result->winnerChange)->toBe(8);
});
