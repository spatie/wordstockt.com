<?php

use App\Domain\Support\Models\Dictionary;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Dictionary::query()->delete();
});

afterEach(function (): void {
    // Clean up any test files
    $basePath = Storage::disk('local')->path('definitions');
    if (is_dir($basePath)) {
        array_map('unlink', glob("{$basePath}/*"));
    }
});

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
    Process::fake([
        '*' => Process::result(exitCode: 1),
    ]);

    $this->artisan('dictionary:import-definitions nl')
        ->expectsOutputToContain('Starting Dutch definitions import')
        ->expectsOutputToContain('Downloading Dutch Wiktionary data')
        ->assertFailed();
});

it('shows correct message for English import', function (): void {
    Process::fake([
        '*' => Process::result(exitCode: 1),
    ]);

    $this->artisan('dictionary:import-definitions en')
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

    Storage::disk('local')->makeDirectory('definitions');
    $storagePath = Storage::disk('local')->path('definitions/en-wiktionary.jsonl');

    Process::fake([
        'curl*' => Process::result(output: '', exitCode: 0),
    ]);

    // Create the file that curl would download
    file_put_contents($storagePath, $jsonlContent);

    $this->artisan('dictionary:import-definitions en')
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

    Storage::disk('local')->makeDirectory('definitions');
    $jsonlPath = Storage::disk('local')->path('definitions/nl-wiktionary.jsonl');
    $gzPath = Storage::disk('local')->path('definitions/nl-wiktionary.jsonl.gz');

    Process::fake([
        'curl*' => Process::result(output: '', exitCode: 0),
        'gunzip*' => Process::result(output: '', exitCode: 0),
    ]);

    // Create the .gz file that curl would download
    file_put_contents($gzPath, 'fake-gz-content');
    // Create the .jsonl file that gunzip would create
    file_put_contents($jsonlPath, $jsonlContent);

    $this->artisan('dictionary:import-definitions nl')
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

    Storage::disk('local')->makeDirectory('definitions');
    $storagePath = Storage::disk('local')->path('definitions/en-wiktionary.jsonl');

    Process::fake([
        'curl*' => Process::result(output: '', exitCode: 0),
    ]);

    file_put_contents($storagePath, $jsonlContent);

    $this->artisan('dictionary:import-definitions en')
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

    Storage::disk('local')->makeDirectory('definitions');
    $storagePath = Storage::disk('local')->path('definitions/en-wiktionary.jsonl');

    Process::fake([
        'curl*' => Process::result(output: '', exitCode: 0),
    ]);

    file_put_contents($storagePath, $jsonlContent);

    $this->artisan('dictionary:import-definitions en')
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

    Storage::disk('local')->makeDirectory('definitions');
    $storagePath = Storage::disk('local')->path('definitions/en-wiktionary.jsonl');

    Process::fake([
        'curl*' => Process::result(output: '', exitCode: 0),
    ]);

    file_put_contents($storagePath, $jsonlContent);

    $this->artisan('dictionary:import-definitions en')
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

    Storage::disk('local')->makeDirectory('definitions');
    $storagePath = Storage::disk('local')->path('definitions/en-wiktionary.jsonl');

    Process::fake([
        'curl*' => Process::result(output: '', exitCode: 0),
    ]);

    file_put_contents($storagePath, $jsonlContent);

    $this->artisan('dictionary:import-definitions en')
        ->assertSuccessful();

    $definition = Dictionary::where('word', 'BIRD')->first()->getDefinitionData();
    expect($definition->proverbs)->toBe([
        'A bird in the hand is worth two in the bush',
        'The early bird catches the worm',
    ]);
});
