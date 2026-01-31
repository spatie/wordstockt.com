<?php

namespace App\Domain\Support\Commands\Dictionary;

use App\Domain\Support\Data\WordDefinitionData;
use App\Domain\Support\Models\Dictionary;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

class ImportEnWordDefinitionsCommand extends Command
{
    protected $signature = 'dictionary:import-en-definitions';

    protected $description = 'Download and import English word definitions from GitHub dictionary';

    private string $jsonPath;

    public function handle(): int
    {
        $this->info('Starting English definitions import...');

        $this->setupPath();

        if (! $this->downloadFile()) {
            return self::FAILURE;
        }

        $this->info('Downloaded definitions file.');

        $definitions = $this->parseDefinitions();

        if (! $definitions) {
            return self::FAILURE;
        }

        $this->info('Importing into database...');

        $updated = $this->importDefinitions($definitions);

        $this->cleanup();

        $this->info("Done! Updated {$updated} dictionary entries.");

        return self::SUCCESS;
    }

    private function setupPath(): void
    {
        Storage::disk('local')->makeDirectory('definitions');

        $this->jsonPath = Storage::disk('local')->path('definitions/en-dictionary.json');
    }

    private function downloadFile(): bool
    {
        $url = 'https://raw.githubusercontent.com/adambom/dictionary/master/dictionary.json';

        $result = Process::timeout(120)->run("curl -sL -o {$this->jsonPath} {$url}");

        if (! $result->successful()) {
            $this->error('Failed to download.');

            return false;
        }

        if (! file_exists($this->jsonPath)) {
            $this->error('Failed to download.');

            return false;
        }

        return true;
    }

    private function parseDefinitions(): ?array
    {
        $content = file_get_contents($this->jsonPath);

        if (! $content) {
            $this->error('Failed to read file.');

            return null;
        }

        $data = json_decode($content, true);

        if (! is_array($data)) {
            $this->error('Failed to parse JSON.');

            return null;
        }

        return $data;
    }

    private function importDefinitions(array $definitions): int
    {
        $updated = 0;
        $chunks = collect($definitions)->chunk(1000);
        $progressBar = $this->output->createProgressBar($chunks->count());
        $progressBar->start();

        foreach ($chunks as $chunk) {
            $words = $chunk->keys()->all();

            $records = Dictionary::where('language', 'en')
                ->whereIn('word', $words)
                ->get();

            foreach ($records as $record) {
                $definitionText = $definitions[$record->word] ?? null;

                if (! $definitionText) {
                    continue;
                }

                $data = new WordDefinitionData(
                    senses: [['definition' => $definitionText]],
                );

                $record->definition = $data->toJson();
                $record->save();
                $updated++;
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();

        return $updated;
    }

    private function cleanup(): void
    {
        if (file_exists($this->jsonPath)) {
            unlink($this->jsonPath);
        }
    }
}
