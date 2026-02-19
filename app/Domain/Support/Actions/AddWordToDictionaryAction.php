<?php

namespace App\Domain\Support\Actions;

use App\Domain\Support\Models\Dictionary;
use App\Jobs\FetchWordDefinitionJob;
use Illuminate\Support\Facades\Cache;

class AddWordToDictionaryAction
{
    public function execute(string $word, string $language): void
    {
        $word = mb_strtoupper(trim($word));

        $existing = Dictionary::query()
            ->where('language', $language)
            ->where('word', $word)
            ->first();

        if ($existing && $existing->is_valid) {
            return;
        }

        if ($existing) {
            $existing->is_valid = true;
            $existing->requested_to_mark_as_invalid_at = null;
            $existing->save();
        } else {
            Dictionary::create([
                'language' => $language,
                'word' => $word,
                'is_valid' => true,
            ]);
        }

        FetchWordDefinitionJob::dispatch($word, $language);

        Cache::forget("dictionary:{$language}:{$word}");
    }
}
