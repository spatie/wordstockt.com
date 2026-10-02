<?php

use App\Domain\Support\Models\Dictionary;

beforeEach(function (): void {
    Dictionary::query()->delete();
});

function writeDefinitionsFixture(string $contents, bool $compressed = false): string
{
    $path = tempnam(sys_get_temp_dir(), 'definitions');

    unlink($path);

    $path .= $compressed ? '.jsonl.gz' : '.jsonl';

    file_put_contents($path, $compressed ? gzencode($contents) : $contents);

    test()->beforeApplicationDestroyed(fn () => @unlink($path));

    return $path;
}

it('fails for unsupported language', function (): void {
    $this->artisan('dictionary:import-definitions fr')
        ->expectsOutputToContain('Unsupported language: fr')
        ->assertFailed();
});

it('fails for invalid language code', function (): void {
    $this->artisan('dictionary:import-definitions invalid')
        ->expectsOutputToContain('Unsupported language: invalid')
        ->assertFailed();
});

it('shows correct message for Dutch import', function (): void {
    $this->artisan('dictionary:import-definitions', ['language' => 'nl', '--source' => '/non/existing/definitions.jsonl'])
        ->expectsOutputToContain('Starting Dutch definitions import')
        ->expectsOutputToContain('Downloading Dutch Wiktionary data')
        ->expectsOutputToContain('Failed to download.')
        ->assertFailed();
});

it('shows correct message for English import', function (): void {
    $this->artisan('dictionary:import-definitions', ['language' => 'en', '--source' => '/non/existing/definitions.jsonl'])
        ->expectsOutputToContain('Starting English definitions import')
        ->expectsOutputToContain('Downloading English Wiktionary data')
        ->assertFailed();
});

it('imports definitions from jsonl file for English', function (): void {
    Dictionary::query()->insert([
        'language' => 'en',
        'word' => 'HOUSE',
        'is_valid' => true,
        'times_played' => 0,
        'definition' => null,
    ]);

    $jsonlContent = json_encode([
        'word' => 'house',
        'lang' => 'English',
        'pos' => 'noun',
        'senses' => [
            ['glosses' => ['A structure built for habitation']],
        ],
        'etymology_text' => 'From Middle English hous',
    ]);

    $source = writeDefinitionsFixture($jsonlContent);

    $this->artisan('dictionary:import-definitions', ['language' => 'en', '--source' => $source])
        ->assertSuccessful();

    $dictionary = Dictionary::where('word', 'HOUSE')->first();
    expect($dictionary->definition)->not->toBeNull();

    $definition = $dictionary->getDefinitionData();
    expect($definition->senses[0]['definition'])->toBe('A structure built for habitation');
    expect($definition->senses[0]['pos'])->toBe('noun');
    expect($definition->etymology)->toBe('From Middle English hous');
});

it('imports definitions from jsonl file for Dutch', function (): void {
    Dictionary::query()->insert([
        'language' => 'nl',
        'word' => 'HUIS',
        'is_valid' => true,
        'times_played' => 0,
        'definition' => null,
    ]);

    $jsonlContent = json_encode([
        'word' => 'huis',
        'lang' => 'Nederlands',
        'pos_title' => 'Zelfstandig naamwoord',
        'senses' => [
            ['glosses' => ['gebouw om in te wonen']],
        ],
        'etymology_texts' => ['Van Middelnederlands huus'],
    ]);

    $source = writeDefinitionsFixture($jsonlContent, compressed: true);

    $this->artisan('dictionary:import-definitions', ['language' => 'nl', '--source' => $source])
        ->assertSuccessful();

    $dictionary = Dictionary::where('word', 'HUIS')->first();
    expect($dictionary->definition)->not->toBeNull();

    $definition = $dictionary->getDefinitionData();
    expect($definition->senses[0]['definition'])->toBe('gebouw om in te wonen');
    expect($definition->senses[0]['pos'])->toBe('Zelfstandig naamwoord');
    expect($definition->etymology)->toBe('Van Middelnederlands huus');
});

it('skips words not in dictionary', function (): void {
    $jsonlContent = json_encode([
        'word' => 'unknownword',
        'lang' => 'English',
        'pos' => 'noun',
        'senses' => [
            ['glosses' => ['Some definition']],
        ],
    ]);

    $source = writeDefinitionsFixture($jsonlContent);

    $this->artisan('dictionary:import-definitions', ['language' => 'en', '--source' => $source])
        ->expectsOutputToContain('Updated 0 dictionary entries')
        ->assertSuccessful();
});

it('skips entries from wrong language', function (): void {
    Dictionary::query()->insert([
        'language' => 'en',
        'word' => 'MAISON',
        'is_valid' => true,
        'times_played' => 0,
        'definition' => null,
    ]);

    $jsonlContent = json_encode([
        'word' => 'maison',
        'lang' => 'French',
        'pos' => 'noun',
        'senses' => [
            ['glosses' => ['House in French']],
        ],
    ]);

    $source = writeDefinitionsFixture($jsonlContent);

    $this->artisan('dictionary:import-definitions', ['language' => 'en', '--source' => $source])
        ->expectsOutputToContain('Updated 0 dictionary entries')
        ->assertSuccessful();

    expect(Dictionary::where('word', 'MAISON')->first()->definition)->toBeNull();
});

it('extracts examples from senses', function (): void {
    Dictionary::query()->insert([
        'language' => 'en',
        'word' => 'RUN',
        'is_valid' => true,
        'times_played' => 0,
        'definition' => null,
    ]);

    $jsonlContent = json_encode([
        'word' => 'run',
        'lang' => 'English',
        'pos' => 'verb',
        'senses' => [
            [
                'glosses' => ['To move swiftly on foot'],
                'examples' => [
                    ['text' => 'He runs every morning.'],
                    ['text' => 'She ran to catch the bus.'],
                ],
            ],
        ],
    ]);

    $source = writeDefinitionsFixture($jsonlContent);

    $this->artisan('dictionary:import-definitions', ['language' => 'en', '--source' => $source])
        ->assertSuccessful();

    $definition = Dictionary::where('word', 'RUN')->first()->getDefinitionData();
    expect($definition->senses[0]['examples'])->toBe([
        'He runs every morning.',
        'She ran to catch the bus.',
    ]);
});

it('extracts proverbs', function (): void {
    Dictionary::query()->insert([
        'language' => 'en',
        'word' => 'BIRD',
        'is_valid' => true,
        'times_played' => 0,
        'definition' => null,
    ]);

    $jsonlContent = json_encode([
        'word' => 'bird',
        'lang' => 'English',
        'pos' => 'noun',
        'senses' => [
            ['glosses' => ['A warm-blooded vertebrate animal']],
        ],
        'proverbs' => [
            ['word' => 'A bird in the hand is worth two in the bush'],
            ['word' => 'The early bird catches the worm'],
        ],
    ]);

    $source = writeDefinitionsFixture($jsonlContent);

    $this->artisan('dictionary:import-definitions', ['language' => 'en', '--source' => $source])
        ->assertSuccessful();

    $definition = Dictionary::where('word', 'BIRD')->first()->getDefinitionData();
    expect($definition->proverbs)->toBe([
        'A bird in the hand is worth two in the bush',
        'The early bird catches the worm',
    ]);
});
