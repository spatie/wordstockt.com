<?php

namespace App\Domain\Support\Actions;

use App\Domain\Support\Models\Dictionary;
use App\Jobs\FetchWordDefinitionJob;

class AddWordToDictionaryAction
{
    public function execute(string $word, string $language): void
    {
        $word = mb_strtoupper(trim($word));

        $exists = Dictionary::query()
            ->where('language', $language)
            ->where('word', $word)
            ->where('is_valid', true)
            ->exists();

        if ($exists) {
            return;
        }

        Dictionary::create([
            'language' => $language,
            'word' => $word,
            'is_valid' => true,
        ]);

        FetchWordDefinitionJob::dispatch($word, $language);
    }
}
