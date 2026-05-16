<?php

use App\Domain\Game\Support\Tile;
use App\Domain\Game\Support\TileBag;

function lettersOf(array $tiles): array
{
    return array_map(fn (Tile $tile): string => $tile->letter, $tiles);
}

function hasConsonant(array $tiles): bool
{
    return collect($tiles)->contains(
        fn (Tile $tile): bool => ! $tile->isBlank && ! in_array($tile->letter, ['A', 'E', 'I', 'O', 'U'], true)
    );
}

function hasVowel(array $tiles): bool
{
    return collect($tiles)->contains(
        fn (Tile $tile): bool => in_array($tile->letter, ['A', 'E', 'I', 'O', 'U'], true)
    );
}

it('leaves a mixed rack untouched and does not reshuffle', function (): void {
    $bag = TileBag::fromArray([
        ['letter' => 'A', 'points' => 1],
        ['letter' => 'B', 'points' => 3],
        ['letter' => 'E', 'points' => 1],
        ['letter' => 'C', 'points' => 5],
    ]);

    $drawn = $bag->drawForRack([], 2);

    expect(lettersOf($drawn))->toBe(['A', 'B']);
    expect(lettersOf($bag->draw(2)))->toBe(['E', 'C']);
});

it('reshuffles and redraws when the rack would be all vowels', function (): void {
    mt_srand(12345);

    $bag = TileBag::fromArray([
        ['letter' => 'A', 'points' => 1],
        ['letter' => 'E', 'points' => 1],
        ['letter' => 'B', 'points' => 3],
        ['letter' => 'C', 'points' => 5],
        ['letter' => 'D', 'points' => 2],
        ['letter' => 'F', 'points' => 4],
        ['letter' => 'G', 'points' => 3],
        ['letter' => 'H', 'points' => 4],
    ]);

    $drawn = $bag->drawForRack([], 2);

    expect(hasConsonant($drawn))->toBeTrue();
});

it('reshuffles and redraws when the rack would be all consonants', function (): void {
    mt_srand(12345);

    $bag = TileBag::fromArray([
        ['letter' => 'B', 'points' => 3],
        ['letter' => 'C', 'points' => 5],
        ['letter' => 'A', 'points' => 1],
        ['letter' => 'E', 'points' => 1],
        ['letter' => 'I', 'points' => 1],
        ['letter' => 'O', 'points' => 1],
        ['letter' => 'U', 'points' => 4],
        ['letter' => 'D', 'points' => 2],
    ]);

    $drawn = $bag->drawForRack([], 2);

    expect(hasVowel($drawn))->toBeTrue();
});

it('does not rebalance when the resulting rack contains a blank', function (): void {
    $keptTiles = [
        new Tile('A', 1),
        new Tile('E', 1),
        new Tile('I', 1),
        new Tile('O', 1),
        new Tile('U', 4),
    ];

    $bag = TileBag::fromArray([
        ['letter' => '*', 'points' => 0, 'is_blank' => true],
        ['letter' => 'B', 'points' => 3],
        ['letter' => 'C', 'points' => 5],
    ]);

    $drawn = $bag->drawForRack($keptTiles, 1);

    expect(lettersOf($drawn))->toBe(['*']);
    expect(lettersOf($bag->draw(2)))->toBe(['B', 'C']);
});

it('retries only once and accepts a still-lopsided rack so bad luck stays possible', function (): void {
    $bag = TileBag::fromArray([
        ['letter' => 'A', 'points' => 1],
        ['letter' => 'E', 'points' => 1],
        ['letter' => 'O', 'points' => 1],
    ]);

    $drawn = $bag->drawForRack([], 3);

    expect(hasConsonant($drawn))->toBeFalse();
    expect($bag->isEmpty())->toBeTrue();
});

it('considers kept rack tiles when rebalancing a refill draw', function (): void {
    mt_srand(12345);

    $keptTiles = [
        new Tile('A', 1),
        new Tile('E', 1),
        new Tile('I', 1),
    ];

    $bag = TileBag::fromArray([
        ['letter' => 'O', 'points' => 1],
        ['letter' => 'U', 'points' => 4],
        ['letter' => 'B', 'points' => 3],
        ['letter' => 'C', 'points' => 5],
        ['letter' => 'D', 'points' => 2],
        ['letter' => 'F', 'points' => 4],
        ['letter' => 'G', 'points' => 3],
    ]);

    $drawn = $bag->drawForRack($keptTiles, 2);

    expect(hasConsonant($drawn))->toBeTrue();
});
