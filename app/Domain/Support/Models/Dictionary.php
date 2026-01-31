<?php

namespace App\Domain\Support\Models;

use App\Domain\Support\Data\WordDefinitionData;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Dictionary extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'times_played' => 'integer',
            'first_played_at' => 'datetime',
            'last_played_at' => 'datetime',
        ];
    }

    public static function isValidWord(string $word, string $language): bool
    {
        $word = mb_strtoupper(trim($word));

        if (strlen($word) < 2) {
            return false;
        }

        return Cache::remember(
            "dictionary:{$language}:{$word}",
            now()->addDay(),
            fn () => static::where('language', $language)->where('word', $word)->exists()
        );
    }

    public static function findInvalidWords(array $words, string $language): array
    {
        return collect($words)
            ->filter(fn (string $word): bool => ! static::isValidWord($word, $language))
            ->values()
            ->all();
    }

    public static function recordPlay(string $word, string $language): void
    {
        $word = mb_strtoupper(trim($word));

        $dictionary = static::where('language', $language)
            ->where('word', $word)
            ->first();

        if (! $dictionary) {
            return;
        }

        $now = now();

        $dictionary->times_played++;
        $dictionary->first_played_at ??= $now;
        $dictionary->last_played_at = $now;
        $dictionary->save();
    }

    public static function recordPlays(array $words, string $language): void
    {
        foreach ($words as $word) {
            static::recordPlay($word, $language);
        }
    }

    public function getDefinitionData(): WordDefinitionData
    {
        return WordDefinitionData::fromJson($this->definition);
    }
}
