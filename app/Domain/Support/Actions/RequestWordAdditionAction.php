<?php

namespace App\Domain\Support\Actions;

use App\Domain\Support\Enums\DictionaryLanguage;
use App\Domain\Support\Models\Dictionary;
use App\Domain\User\Models\User;
use App\Jobs\SendWordRequestedMailJob;

class RequestWordAdditionAction
{
    public function execute(string $word, string $language, User $requester): void
    {
        $word = mb_strtoupper(trim($word));

        $dictionary = Dictionary::query()->firstOrCreate([
            'language' => $language,
            'word' => $word,
        ], [
            'is_valid' => false,
        ]);

        if ($dictionary->is_valid) {
            return;
        }

        Dictionary::query()
            ->where('language', $language)
            ->where('word', $word)
            ->whereNull('requested_by_user_id')
            ->update(['requested_by_user_id' => $requester->id]);

        SendWordRequestedMailJob::dispatch($word, DictionaryLanguage::from($language), $requester);
    }
}
