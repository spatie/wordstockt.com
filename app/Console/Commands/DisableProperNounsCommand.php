<?php

namespace App\Console\Commands;

use App\Domain\Support\Enums\DictionaryLanguage;
use App\Domain\Support\Models\Dictionary;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

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

        $this->info(($isDry ? '[DRY RUN] ' : '').'Scanning for proper nouns...');

        $totalDisabled = 0;

        Dictionary::query()
            ->where('language', $language->value)
            ->where('is_valid', true)
            ->whereNotNull('definition')
            ->chunkById(1000, function ($entries) use ($isDry, $language, &$totalDisabled) {
                $properNouns = $entries->filter(fn (Dictionary $entry) => $entry->isProperNoun());

                if ($properNouns->isEmpty()) {
                    return;
                }

                foreach ($properNouns as $entry) {
                    $this->line("  - {$entry->word}");
                }

                $totalDisabled += $properNouns->count();

                if ($isDry) {
                    return;
                }

                Dictionary::query()
                    ->whereIn('id', $properNouns->pluck('id'))
                    ->update(['is_valid' => false, 'requested_to_mark_as_invalid_at' => null]);

                foreach ($properNouns as $entry) {
                    Cache::forget("dictionary:{$language->value}:{$entry->word}");
                }
            });

        if ($totalDisabled === 0) {
            $this->info('No proper nouns found to disable.');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->info("Found {$totalDisabled} proper nouns to disable.");

        if ($isDry) {
            $this->info('Run without --dry to disable these words.');

            return self::SUCCESS;
        }

        $this->info("Disabled {$totalDisabled} proper nouns.");

        return self::SUCCESS;
    }
}
