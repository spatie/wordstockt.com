<?php

use App\Domain\Support\Enums\DictionaryLanguage;

it('has correct labels', function (): void {
    expect(DictionaryLanguage::Dutch->label())->toBe('Dutch');
    expect(DictionaryLanguage::English->label())->toBe('English');
});

it('has correct definitions urls', function (): void {
    expect(DictionaryLanguage::Dutch->definitionsUrl())
        ->toBe('https://kaikki.org/nlwiktionary/raw-wiktextract-data.jsonl.gz');

    expect(DictionaryLanguage::English->definitionsUrl())
        ->toBe('https://kaikki.org/dictionary/English/kaikki.org-dictionary-English.jsonl');
});

it('knows which files are compressed', function (): void {
    expect(DictionaryLanguage::Dutch->isCompressed())->toBeTrue();
    expect(DictionaryLanguage::English->isCompressed())->toBeFalse();
});

it('has correct wiktionary language identifiers', function (): void {
    expect(DictionaryLanguage::Dutch->wiktionaryLanguageIdentifier())->toBe('Nederlands');
    expect(DictionaryLanguage::English->wiktionaryLanguageIdentifier())->toBe('English');
});

it('has correct etymology field names', function (): void {
    expect(DictionaryLanguage::Dutch->etymologyField())->toBe('etymology_texts');
    expect(DictionaryLanguage::English->etymologyField())->toBe('etymology_text');
});

it('has correct pos field names', function (): void {
    expect(DictionaryLanguage::Dutch->posField())->toBe('pos_title');
    expect(DictionaryLanguage::English->posField())->toBe('pos');
});

it('has correct proper noun pos values', function (): void {
    expect(DictionaryLanguage::Dutch->properNounPos())->toBe('Eigennaam');
    expect(DictionaryLanguage::English->properNounPos())->toBe('name');
});

it('has correct download timeouts', function (): void {
    expect(DictionaryLanguage::Dutch->downloadTimeout())->toBe(600);
    expect(DictionaryLanguage::English->downloadTimeout())->toBe(1800);
});

it('can be created from language code', function (): void {
    expect(DictionaryLanguage::from('nl'))->toBe(DictionaryLanguage::Dutch);
    expect(DictionaryLanguage::from('en'))->toBe(DictionaryLanguage::English);
});

it('returns null for invalid language code with tryFrom', function (): void {
    expect(DictionaryLanguage::tryFrom('fr'))->toBeNull();
    expect(DictionaryLanguage::tryFrom('invalid'))->toBeNull();
});
