<?php

namespace App\Domain\Support\Commands\Dictionary;

use App\Domain\Support\Models\Dictionary;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ImportDictionaryWordsCommand extends Command
{
    protected $signature = 'dictionaries:import-words
                            {language? : The language code (nl, en). Imports all if omitted}
                            {--file= : Path to a local word list file}
                            {--url= : URL to download word list from}';

    protected $description = 'Import a dictionary word list for a specific language (or all languages)';

    public function handle(): int
    {
        $language = $this->argument('language');

        if ($language) {
            return $this->importLanguage($language);
        }

        foreach ($this->supportedLanguages() as $lang) {
            $result = $this->importLanguage($lang);

            if ($result === self::FAILURE) {
                return self::FAILURE;
            }

            $this->newLine();
        }

        return self::SUCCESS;
    }

    private function importLanguage(string $language): int
    {
        $defaultSource = $this->defaultSourceUrl($language);

        if (! $defaultSource) {
            $this->error("Unsupported language: {$language}. Use: ".implode(', ', $this->supportedLanguages()));

            return self::FAILURE;
        }

        $this->info("Importing {$language}...");

        $words = $this->loadWordList($this->option('file'), $this->option('url') ?? $defaultSource);

        if ($words === null) {
            return self::FAILURE;
        }

        $words = $this->normalizeWords($words);

        $this->info("Found {$words->count()} valid words.");

        $existingCount = Dictionary::where('language', $language)->count();

        $this->info('Importing words...');
        $this->withProgressBar($words->chunk(1000), function (Collection $chunk) use ($language) {
            $records = $chunk->map(fn (string $word) => ['language' => $language, 'word' => $word])->all();
            DB::table('dictionaries')->insertOrIgnore($records);
        });

        $this->newLine();

        $total = Dictionary::where('language', $language)->count();
        $this->info('Added '.($total - $existingCount)." new words. Total: {$total} words for {$language}.");

        return self::SUCCESS;
    }

    private function supportedLanguages(): array
    {
        return ['nl', 'en'];
    }

    private function normalizeWords(array $words): Collection
    {
        return collect($words)
            ->map(fn ($word) => Str::upper(trim((string) $word)))
            ->filter(fn (string $word) => Str::length($word) >= 2)
            ->filter(fn (string $word) => Str::length($word) <= 15)
            ->filter(fn (string $word) => preg_match('/^[a-zA-Z]+$/u', $word))
            ->unique()
            ->values();
    }

    private function defaultSourceUrl(string $language): ?string
    {
        return match ($language) {
            'nl' => 'https://raw.githubusercontent.com/OpenTaal/opentaal-wordlist/master/wordlist.txt',
            'en' => 'https://raw.githubusercontent.com/dwyl/english-words/master/words_alpha.txt',
            default => null,
        };
    }

    private function loadWordList(?string $file, string $url): ?array
    {
        if ($file) {
            return $this->loadFromFile($file);
        }

        return $this->loadFromUrl($url);
    }

    private function loadFromFile(string $file): ?array
    {
        if (! file_exists($file)) {
            $this->error("File not found: {$file}");

            return null;
        }

        return file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    }

    private function loadFromUrl(string $url): ?array
    {
        $this->info("Downloading word list from {$url}...");

        try {
            $response = Http::timeout(120)->get($url);

            if (! $response->successful()) {
                $this->error('Failed to download word list.');

                return null;
            }

            return explode("\n", $response->body());
        } catch (\Exception $exception) {
            $this->error('Failed to download: '.$exception->getMessage());

            return null;
        }
    }
}
