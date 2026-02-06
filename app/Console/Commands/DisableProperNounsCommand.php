<?php

namespace App\Console\Commands;

use App\Domain\Support\Enums\DictionaryLanguage;
use App\Domain\Support\Models\Dictionary;
use Illuminate\Console\Command;

class DisableProperNounsCommand extends Command
{
    protected $signature = 'dictionary:disable-proper-nouns
                            {language : The language to process (nl or en)}
                            {--dry : Only display words that would be disabled}';

    protected $description = 'Disable proper nouns (words where all senses are proper nouns)';

    public function handle(): int
    {
        $languageCode = $this->argument('language');
        $isDry = $this->option('dry');

        $language = DictionaryLanguage::tryFrom($languageCode);

        if (! $language) {
            $this->error("Unsupported language: {$languageCode}. Supported: nl, en");

            return self::FAILURE;
        }

        $properNouns = Dictionary::query()
            ->where('language', $language->value)
            ->where('is_valid', true)
            ->whereNotNull('definition')
            ->get()
            ->filter(fn (Dictionary $entry) => $entry->isProperNoun());

        if ($properNouns->isEmpty()) {
            $this->info('No proper nouns found to disable.');

            return self::SUCCESS;
        }

        $this->info(($isDry ? '[DRY RUN] ' : '')."Found {$properNouns->count()} proper nouns to disable:");
        $this->newLine();

        foreach ($properNouns as $entry) {
            $this->line("  - {$entry->word}");
        }

        if ($isDry) {
            $this->newLine();
            $this->info('Run without --dry to disable these words.');

            return self::SUCCESS;
        }

        $this->newLine();

        foreach ($properNouns as $entry) {
            $entry->invalidate();
        }

        $this->info("Disabled {$properNouns->count()} proper nouns.");

        return self::SUCCESS;
    }
}
