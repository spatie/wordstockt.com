<?php

use App\Domain\User\Support\EloCalculator\MultiplayerEloCalculator;

it('returns the average pairwise change for a clear win over two equal opponents', function (): void {
    $calc = new MultiplayerEloCalculator(kFactor: 32, scaleFactor: 400);

    // beats two 1500-rated opponents, self at 1500
    $change = $calc->netChange(1500, [
        ['elo' => 1500, 'score' => 1.0],
        ['elo' => 1500, 'score' => 1.0],
    ]);

    // each pairwise: 32 * (1 - 0.5) = 16; average of (16,16) = 16
    expect($change)->toBe(16);
});

it('nets a win and a loss against equal opponents to roughly zero', function (): void {
    $calc = new MultiplayerEloCalculator(kFactor: 32, scaleFactor: 400);

    $change = $calc->netChange(1500, [
        ['elo' => 1500, 'score' => 1.0],
        ['elo' => 1500, 'score' => 0.0],
    ]);

    // (16 + -16) / 2 = 0
    expect($change)->toBe(0);
});

it('treats a tie as half a point', function (): void {
    $calc = new MultiplayerEloCalculator(kFactor: 32, scaleFactor: 400);

    $change = $calc->netChange(1500, [['elo' => 1500, 'score' => 0.5]]);

    expect($change)->toBe(0);
});
