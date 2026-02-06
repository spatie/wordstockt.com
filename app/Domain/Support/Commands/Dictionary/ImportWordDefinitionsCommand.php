<?php

namespace App\Domain\Support\Commands\Dictionary;

use App\Domain\Support\Data\WordDefinitionData;
use App\Domain\Support\Enums\DictionaryLanguage;
use App\Domain\Support\Models\Dictionary;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use SplFileObject;

class ImportWordDefinitionsCommand extends Command
{
    protected $signature = 'dictionary:import-definitions {language : The language to import (nl or en)}';

    protected $description = 'Download and import word definitions from Wiktionary';

    private string $downloadPath;

    private string $jsonlPath;

    private DictionaryLanguage $language;

    public function handle(): int
    {
        $languageCode = $this->argument('language');

        $language = DictionaryLanguage::tryFrom($languageCode);

        if (! $language) {
            $this->error("Unsupported language: {$languageCode}. Supported: nl, en");

            return self::FAILURE;
        }

        $this->language = $language;

        $this->info("Starting {$this->language->label()} definitions import...");

        $this->setupPaths();

        if (! $this->downloadFile()) {
            return self::FAILURE;
        }

        if ($this->language->isCompressed() && ! $this->extractFile()) {
            return self::FAILURE;
        }

        $this->info('Downloaded definitions file.');

        $updated = $this->parseAndImport();

        $this->cleanup();

        $this->info("Done! Updated {$updated} dictionary entries.");

        return self::SUCCESS;
    }

    private function setupPaths(): void
    {
        Storage::disk('local')->makeDirectory('definitions');

        $basePath = Storage::disk('local')->path('definitions');
        $this->jsonlPath = "{$basePath}/{$this->language->value}-wiktionary.jsonl";
        $this->downloadPath = $this->language->isCompressed()
            ? "{$this->jsonlPath}.gz"
            : $this->jsonlPath;
    }

    private function downloadFile(): bool
    {
        $url = $this->language->definitionsUrl();
        $timeout = $this->language->downloadTimeout();

        $this->info("Downloading {$this->language->label()} Wiktionary data...");

        $result = Process::timeout($timeout)->run("curl -sL -o {$this->downloadPath} {$url}");

        if (! $result->successful()) {
            $this->error('Failed to download.');

            return false;
        }

        if (! file_exists($this->downloadPath)) {
            $this->error('Failed to download.');

            return false;
        }

        return true;
    }

    private function extractFile(): bool
    {
        Process::run("gunzip -f {$this->downloadPath}");

        if (! file_exists($this->jsonlPath)) {
            $this->error('Failed to extract.');

            return false;
        }

        return true;
    }

    private function parseAndImport(): int
    {
        $this->info('Loading dictionary words...');

        $dictionaryWords = Dictionary::where('language', $this->language->value)
            ->pluck('word')
            ->flip()
            ->all();

        $this->info('Found '.count($dictionaryWords).' words in dictionary.');

        $file = new SplFileObject($this->jsonlPath, 'r');

        $this->info('Parsing definitions file...');

        $wordData = [];
        $updated = 0;
        $batchSize = 5000;
        $lineCount = 0;

        while (! $file->eof()) {
            $line = $file->fgets();
            $lineCount++;

            if ($lineCount % 100000 === 0) {
                $this->output->write("\rProcessed {$lineCount} lines, found ".count($wordData).' matching words...');
            }

            $entry = $this->parseLine($line);

            if (! $entry) {
                continue;
            }

            if (! $this->isCorrectLanguage($entry)) {
                continue;
            }

            $word = $this->extractWord($entry);

            if (! $word) {
                continue;
            }

            if (! isset($dictionaryWords[$word])) {
                continue;
            }

            if (! isset($wordData[$word])) {
                $wordData[$word] = [
                    'senses' => [],
                    'etymology' => null,
                    'proverbs' => [],
                ];
            }

            $pos = $this->extractPos($entry);

            foreach ($entry['senses'] ?? [] as $sense) {
                $definition = $sense['glosses'][0] ?? null;

                if (! $definition) {
                    continue;
                }

                if (count($wordData[$word]['senses']) >= 10) {
                    continue;
                }

                $examples = collect($sense['examples'] ?? [])
                    ->pluck('text')
                    ->filter()
                    ->take(2)
                    ->values()
                    ->all();

                $wordData[$word]['senses'][] = [
                    'definition' => $definition,
                    'pos' => $pos,
                    'examples' => $examples ?: null,
                ];
            }

            if (! $wordData[$word]['etymology']) {
                $wordData[$word]['etymology'] = $this->extractEtymology($entry);
            }

            if (count($wordData[$word]['proverbs']) < 5) {
                $entryProverbs = collect($entry['proverbs'] ?? [])
                    ->pluck('word')
                    ->filter()
                    ->take(5 - count($wordData[$word]['proverbs']))
                    ->all();

                $wordData[$word]['proverbs'] = array_merge(
                    $wordData[$word]['proverbs'],
                    $entryProverbs
                );
            }

            if (count($wordData) >= $batchSize) {
                $updated += $this->importBatch($wordData);
                $this->info("Imported batch... {$updated} words updated so far.");
                $wordData = [];
            }
        }

        if (count($wordData) > 0) {
            $updated += $this->importBatch($wordData);
        }

        $this->newLine();

        return $updated;
    }

    private function importBatch(array $wordData): int
    {
        $updated = 0;

        $words = array_keys($wordData);

        $records = Dictionary::where('language', $this->language->value)
            ->whereIn('word', $words)
            ->get();

        foreach ($records as $record) {
            $data = $wordData[$record->word] ?? null;

            if (! $data || empty($data['senses'])) {
                continue;
            }

            $proverbs = array_unique($data['proverbs']);

            $definition = new WordDefinitionData(
                senses: $data['senses'],
                etymology: $data['etymology'],
                proverbs: $proverbs ?: null,
            );

            $record->definition = $definition->toJson();
            $record->save();
            $updated++;
        }

        return $updated;
    }

    private function parseLine(string $line): ?array
    {
        $line = trim($line);

        if (empty($line)) {
            return null;
        }

        return json_decode($line, true);
    }

    private function isCorrectLanguage(array $entry): bool
    {
        return ($entry['lang'] ?? '') === $this->language->wiktionaryLanguageIdentifier();
    }

    private function extractWord(array $entry): ?string
    {
        $word = $entry['word'] ?? '';

        if (empty($word)) {
            return null;
        }

        return mb_strtoupper($word);
    }

    private function extractPos(array $entry): ?string
    {
        $field = $this->language->posField();

        return $entry[$field] ?? null;
    }

    private function extractEtymology(array $entry): ?string
    {
        $field = $this->language->etymologyField();

        if ($field === 'etymology_texts') {
            return $entry[$field][0] ?? null;
        }

        return $entry[$field] ?? null;
    }

    private function cleanup(): void
    {
        if (file_exists($this->jsonlPath)) {
            unlink($this->jsonlPath);
        }

        if (file_exists($this->downloadPath) && $this->downloadPath !== $this->jsonlPath) {
            unlink($this->downloadPath);
        }
    }
}
