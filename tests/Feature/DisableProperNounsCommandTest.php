<?php

use App\Domain\Support\Models\Dictionary;

beforeEach(function (): void {
    Dictionary::query()->delete();
});

it('disables Dutch proper nouns where all senses have pos Eigennaam', function (): void {
    Dictionary::query()->insert([
        'language' => 'nl',
        'word' => 'ARNO',
        'is_valid' => true,
        'times_played' => 0,
        'definition' => json_encode([
            'senses' => [
                ['definition' => 'jongensnaam', 'pos' => 'Eigennaam'],
                ['definition' => 'rivier in Italië', 'pos' => 'Eigennaam'],
            ],
        ]),
    ]);

    $this->artisan('dictionary:disable-proper-nouns nl')
        ->expectsOutputToContain('Found 1 proper nouns to disable')
        ->expectsOutputToContain('ARNO')
        ->assertSuccessful();

    expect(Dictionary::where('word', 'ARNO')->first()->is_valid)->toBeFalse();
});

it('disables English proper nouns where all senses have pos name', function (): void {
    Dictionary::query()->insert([
        'language' => 'en',
        'word' => 'JOHN',
        'is_valid' => true,
        'times_played' => 0,
        'definition' => json_encode([
            'senses' => [
                ['definition' => 'A male given name', 'pos' => 'name'],
                ['definition' => 'A surname', 'pos' => 'name'],
            ],
        ]),
    ]);

    $this->artisan('dictionary:disable-proper-nouns en')
        ->expectsOutputToContain('Found 1 proper nouns to disable')
        ->expectsOutputToContain('JOHN')
        ->assertSuccessful();

    expect(Dictionary::where('word', 'JOHN')->first()->is_valid)->toBeFalse();
});

it('does not disable Dutch words with mixed senses', function (): void {
    Dictionary::query()->insert([
        'language' => 'nl',
        'word' => 'WATER',
        'is_valid' => true,
        'times_played' => 0,
        'definition' => json_encode([
            'senses' => [
                ['definition' => 'vloeistof', 'pos' => 'Zelfstandig naamwoord'],
                ['definition' => 'rivier', 'pos' => 'Eigennaam'],
            ],
        ]),
    ]);

    $this->artisan('dictionary:disable-proper-nouns nl')
        ->expectsOutputToContain('No proper nouns found to disable')
        ->assertSuccessful();

    expect(Dictionary::where('word', 'WATER')->first()->is_valid)->toBeTrue();
});

it('does not disable English words with mixed senses', function (): void {
    Dictionary::query()->insert([
        'language' => 'en',
        'word' => 'ROSE',
        'is_valid' => true,
        'times_played' => 0,
        'definition' => json_encode([
            'senses' => [
                ['definition' => 'A flower', 'pos' => 'noun'],
                ['definition' => 'A female given name', 'pos' => 'name'],
            ],
        ]),
    ]);

    $this->artisan('dictionary:disable-proper-nouns en')
        ->expectsOutputToContain('No proper nouns found to disable')
        ->assertSuccessful();

    expect(Dictionary::where('word', 'ROSE')->first()->is_valid)->toBeTrue();
});

it('does not disable words without definitions', function (): void {
    Dictionary::query()->insert([
        'language' => 'nl',
        'word' => 'HUIS',
        'is_valid' => true,
        'times_played' => 0,
        'definition' => null,
    ]);

    $this->artisan('dictionary:disable-proper-nouns nl')
        ->expectsOutputToContain('No proper nouns found to disable')
        ->assertSuccessful();

    expect(Dictionary::where('word', 'HUIS')->first()->is_valid)->toBeTrue();
});

it('does not disable words with empty senses array', function (): void {
    Dictionary::query()->insert([
        'language' => 'nl',
        'word' => 'TEST',
        'is_valid' => true,
        'times_played' => 0,
        'definition' => json_encode(['senses' => []]),
    ]);

    $this->artisan('dictionary:disable-proper-nouns nl')
        ->expectsOutputToContain('No proper nouns found to disable')
        ->assertSuccessful();

    expect(Dictionary::where('word', 'TEST')->first()->is_valid)->toBeTrue();
});

it('only affects the specified language', function (): void {
    Dictionary::query()->insert([
        'language' => 'en',
        'word' => 'JOHN',
        'is_valid' => true,
        'times_played' => 0,
        'definition' => json_encode([
            'senses' => [
                ['definition' => 'A male given name', 'pos' => 'name'],
            ],
        ]),
    ]);

    $this->artisan('dictionary:disable-proper-nouns nl')
        ->expectsOutputToContain('No proper nouns found to disable')
        ->assertSuccessful();

    expect(Dictionary::where('word', 'JOHN')->first()->is_valid)->toBeTrue();
});

it('does not disable already invalid words', function (): void {
    Dictionary::query()->insert([
        'language' => 'nl',
        'word' => 'PIET',
        'is_valid' => false,
        'times_played' => 0,
        'definition' => json_encode([
            'senses' => [
                ['definition' => 'jongensnaam', 'pos' => 'Eigennaam'],
            ],
        ]),
    ]);

    $this->artisan('dictionary:disable-proper-nouns nl')
        ->expectsOutputToContain('No proper nouns found to disable')
        ->assertSuccessful();
});

it('shows words in dry mode without disabling them', function (): void {
    Dictionary::query()->insert([
        'language' => 'nl',
        'word' => 'MARIA',
        'is_valid' => true,
        'times_played' => 0,
        'definition' => json_encode([
            'senses' => [
                ['definition' => 'meisjesnaam', 'pos' => 'Eigennaam'],
            ],
        ]),
    ]);

    $this->artisan('dictionary:disable-proper-nouns nl --dry')
        ->expectsOutputToContain('[DRY RUN]')
        ->expectsOutputToContain('MARIA')
        ->expectsOutputToContain('Run without --dry to disable these words')
        ->assertSuccessful();

    expect(Dictionary::where('word', 'MARIA')->first()->is_valid)->toBeTrue();
});

it('fails for unsupported language', function (): void {
    $this->artisan('dictionary:disable-proper-nouns fr')
        ->expectsOutputToContain('Unsupported language: fr')
        ->assertFailed();
});
