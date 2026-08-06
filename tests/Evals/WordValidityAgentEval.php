<?php

use App\Domain\Support\Agents\WordValidityAgent;
use App\Domain\Support\Enums\DictionaryLanguage;

it('accepts an ordinary Dutch verb', function (): void {
    expect(dutchRecommendation())
        ->prompt('Word: BLAFFEN')
        ->toBe('add');
});

it('rejects a Dutch proper noun', function (): void {
    expect(dutchRecommendation())
        ->prompt('Word: AMSTERDAM')
        ->toBe('reject');
});

it('does not accept a nonsense letter sequence', function (): void {
    expect(dutchRecommendation())
        ->prompt('Word: XQZPLF')
        ->not->toBe('add');
});

it('accepts an ordinary English participle', function (): void {
    expect(englishRecommendation())
        ->prompt('Word: BLUSTERING')
        ->toBe('add');
});

it('rejects an English acronym', function (): void {
    expect(englishRecommendation())
        ->prompt('Word: NASA')
        ->toBe('reject');
});

function dutchRecommendation(): Closure
{
    return recommendationIn(DictionaryLanguage::Dutch);
}

function englishRecommendation(): Closure
{
    return recommendationIn(DictionaryLanguage::English);
}

function recommendationIn(DictionaryLanguage $language): Closure
{
    return fn (string $prompt): string => (new WordValidityAgent($language))->prompt($prompt)['recommendation'];
}
