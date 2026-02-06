<?php

use App\Domain\Support\Models\Dictionary;

it('returns true for Dutch word with all Eigennaam senses', function (): void {
    $dictionary = new Dictionary([
        'language' => 'nl',
        'word' => 'ARNO',
        'definition' => json_encode([
            'senses' => [
                ['definition' => 'jongensnaam', 'pos' => 'Eigennaam'],
                ['definition' => 'rivier', 'pos' => 'Eigennaam'],
            ],
        ]),
    ]);

    expect($dictionary->isProperNoun())->toBeTrue();
});

it('returns true for English word with all name senses', function (): void {
    $dictionary = new Dictionary([
        'language' => 'en',
        'word' => 'JOHN',
        'definition' => json_encode([
            'senses' => [
                ['definition' => 'A male given name', 'pos' => 'name'],
            ],
        ]),
    ]);

    expect($dictionary->isProperNoun())->toBeTrue();
});

it('returns false for Dutch word with mixed senses', function (): void {
    $dictionary = new Dictionary([
        'language' => 'nl',
        'word' => 'WATER',
        'definition' => json_encode([
            'senses' => [
                ['definition' => 'vloeistof', 'pos' => 'Zelfstandig naamwoord'],
                ['definition' => 'rivier', 'pos' => 'Eigennaam'],
            ],
        ]),
    ]);

    expect($dictionary->isProperNoun())->toBeFalse();
});

it('returns false for English word with mixed senses', function (): void {
    $dictionary = new Dictionary([
        'language' => 'en',
        'word' => 'ROSE',
        'definition' => json_encode([
            'senses' => [
                ['definition' => 'A flower', 'pos' => 'noun'],
                ['definition' => 'A female given name', 'pos' => 'name'],
            ],
        ]),
    ]);

    expect($dictionary->isProperNoun())->toBeFalse();
});

it('returns false when definition is null', function (): void {
    $dictionary = new Dictionary([
        'language' => 'nl',
        'word' => 'TEST',
        'definition' => null,
    ]);

    expect($dictionary->isProperNoun())->toBeFalse();
});

it('returns false when senses array is empty', function (): void {
    $dictionary = new Dictionary([
        'language' => 'nl',
        'word' => 'TEST',
        'definition' => json_encode(['senses' => []]),
    ]);

    expect($dictionary->isProperNoun())->toBeFalse();
});

it('returns false when sense has no pos field', function (): void {
    $dictionary = new Dictionary([
        'language' => 'nl',
        'word' => 'TEST',
        'definition' => json_encode([
            'senses' => [
                ['definition' => 'some definition'],
            ],
        ]),
    ]);

    expect($dictionary->isProperNoun())->toBeFalse();
});
