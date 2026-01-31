<?php

use App\Domain\Support\Commands\Dictionary\ImportDictionaryWordsCommand;
use App\Domain\Support\Models\Dictionary;
use Illuminate\Support\Facades\Http;

it('imports words from a local file', function (): void {
    $file = tempnam(sys_get_temp_dir(), 'dict');
    file_put_contents($file, "test\nword\nexample\n");

    $this->artisan(ImportDictionaryWordsCommand::class, ['language' => 'nl', '--file' => $file])
        ->assertSuccessful();

    expect(Dictionary::where('language', 'nl')->count())->toBe(3);
    expect(Dictionary::where('language', 'nl')->where('word', 'TEST')->exists())->toBeTrue();
    expect(Dictionary::where('language', 'nl')->where('word', 'WORD')->exists())->toBeTrue();
    expect(Dictionary::where('language', 'nl')->where('word', 'EXAMPLE')->exists())->toBeTrue();

    unlink($file);
});

it('imports words from a url', function (): void {
    Http::fake([
        'https://example.com/words.txt' => Http::response("hello\nworld\n"),
    ]);

    $this->artisan(ImportDictionaryWordsCommand::class, ['language' => 'en', '--url' => 'https://example.com/words.txt'])
        ->assertSuccessful();

    expect(Dictionary::where('language', 'en')->count())->toBe(2);
    expect(Dictionary::where('language', 'en')->where('word', 'HELLO')->exists())->toBeTrue();
    expect(Dictionary::where('language', 'en')->where('word', 'WORLD')->exists())->toBeTrue();
});

it('uses default source when no file or url provided', function (): void {
    Http::fake([
        'https://raw.githubusercontent.com/OpenTaal/opentaal-wordlist/master/wordlist.txt' => Http::response("huis\nboom\n"),
    ]);

    $this->artisan(ImportDictionaryWordsCommand::class, ['language' => 'nl'])
        ->assertSuccessful();

    expect(Dictionary::where('language', 'nl')->count())->toBe(2);
});

it('rejects unsupported languages', function (): void {
    $this->artisan(ImportDictionaryWordsCommand::class, ['language' => 'fr'])
        ->assertFailed();
});

it('filters out words that are too short', function (): void {
    $file = tempnam(sys_get_temp_dir(), 'dict');
    file_put_contents($file, "a\nab\nabc\n");

    $this->artisan(ImportDictionaryWordsCommand::class, ['language' => 'nl', '--file' => $file])
        ->assertSuccessful();

    expect(Dictionary::where('language', 'nl')->count())->toBe(2);
    expect(Dictionary::where('language', 'nl')->where('word', 'A')->exists())->toBeFalse();
    expect(Dictionary::where('language', 'nl')->where('word', 'AB')->exists())->toBeTrue();
    expect(Dictionary::where('language', 'nl')->where('word', 'ABC')->exists())->toBeTrue();

    unlink($file);
});

it('filters out words that are too long', function (): void {
    $file = tempnam(sys_get_temp_dir(), 'dict');
    file_put_contents($file, "short\nverylongwordthatexceedslimit\n");

    $this->artisan(ImportDictionaryWordsCommand::class, ['language' => 'nl', '--file' => $file])
        ->assertSuccessful();

    expect(Dictionary::where('language', 'nl')->count())->toBe(1);
    expect(Dictionary::where('language', 'nl')->where('word', 'SHORT')->exists())->toBeTrue();

    unlink($file);
});

it('filters out words with non-letter characters', function (): void {
    $file = tempnam(sys_get_temp_dir(), 'dict');
    file_put_contents($file, "test\ntest123\ntest-word\ntest_word\n");

    $this->artisan(ImportDictionaryWordsCommand::class, ['language' => 'nl', '--file' => $file])
        ->assertSuccessful();

    expect(Dictionary::where('language', 'nl')->count())->toBe(1);
    expect(Dictionary::where('language', 'nl')->where('word', 'TEST')->exists())->toBeTrue();

    unlink($file);
});

it('normalizes words to uppercase', function (): void {
    $file = tempnam(sys_get_temp_dir(), 'dict');
    file_put_contents($file, "Test\nWORD\nmixed\n");

    $this->artisan(ImportDictionaryWordsCommand::class, ['language' => 'nl', '--file' => $file])
        ->assertSuccessful();

    expect(Dictionary::where('language', 'nl')->where('word', 'TEST')->exists())->toBeTrue();
    expect(Dictionary::where('language', 'nl')->where('word', 'WORD')->exists())->toBeTrue();
    expect(Dictionary::where('language', 'nl')->where('word', 'MIXED')->exists())->toBeTrue();

    unlink($file);
});

it('does not duplicate existing words', function (): void {
    Dictionary::create(['language' => 'nl', 'word' => 'TEST']);

    $file = tempnam(sys_get_temp_dir(), 'dict');
    file_put_contents($file, "test\nword\n");

    $this->artisan(ImportDictionaryWordsCommand::class, ['language' => 'nl', '--file' => $file])
        ->assertSuccessful();

    expect(Dictionary::where('language', 'nl')->count())->toBe(2);
    expect(Dictionary::where('language', 'nl')->where('word', 'TEST')->count())->toBe(1);

    unlink($file);
});

it('does not delete existing words', function (): void {
    Dictionary::create(['language' => 'nl', 'word' => 'EXISTING']);
    Dictionary::create(['language' => 'nl', 'word' => 'ANOTHER']);

    $file = tempnam(sys_get_temp_dir(), 'dict');
    file_put_contents($file, "newword\n");

    $this->artisan(ImportDictionaryWordsCommand::class, ['language' => 'nl', '--file' => $file])
        ->assertSuccessful();

    expect(Dictionary::where('language', 'nl')->count())->toBe(3);
    expect(Dictionary::where('language', 'nl')->where('word', 'EXISTING')->exists())->toBeTrue();
    expect(Dictionary::where('language', 'nl')->where('word', 'ANOTHER')->exists())->toBeTrue();
    expect(Dictionary::where('language', 'nl')->where('word', 'NEWWORD')->exists())->toBeTrue();

    unlink($file);
});

it('fails when file does not exist', function (): void {
    $this->artisan(ImportDictionaryWordsCommand::class, ['language' => 'nl', '--file' => '/nonexistent/file.txt'])
        ->assertFailed();
});

it('fails when url returns error', function (): void {
    Http::fake([
        'https://example.com/words.txt' => Http::response('Not found', 404),
    ]);

    $this->artisan(ImportDictionaryWordsCommand::class, ['language' => 'nl', '--url' => 'https://example.com/words.txt'])
        ->assertFailed();
});

it('handles empty word list file', function (): void {
    $file = tempnam(sys_get_temp_dir(), 'dict');
    file_put_contents($file, '');

    $this->artisan(ImportDictionaryWordsCommand::class, ['language' => 'nl', '--file' => $file])
        ->assertSuccessful();

    expect(Dictionary::where('language', 'nl')->count())->toBe(0);

    unlink($file);
});

it('separates words by language', function (): void {
    $nlFile = tempnam(sys_get_temp_dir(), 'dict');
    file_put_contents($nlFile, "huis\n");

    $enFile = tempnam(sys_get_temp_dir(), 'dict');
    file_put_contents($enFile, "house\n");

    $this->artisan(ImportDictionaryWordsCommand::class, ['language' => 'nl', '--file' => $nlFile])
        ->assertSuccessful();
    $this->artisan(ImportDictionaryWordsCommand::class, ['language' => 'en', '--file' => $enFile])
        ->assertSuccessful();

    expect(Dictionary::where('language', 'nl')->count())->toBe(1);
    expect(Dictionary::where('language', 'en')->count())->toBe(1);
    expect(Dictionary::where('language', 'nl')->where('word', 'HUIS')->exists())->toBeTrue();
    expect(Dictionary::where('language', 'en')->where('word', 'HOUSE')->exists())->toBeTrue();

    unlink($nlFile);
    unlink($enFile);
});
