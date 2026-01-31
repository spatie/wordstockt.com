<?php

namespace App\Domain\Achievement\Actions;

use App\Domain\Achievement\Models\UserWordPlay;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\Move;
use App\Domain\Support\Models\Dictionary;
use App\Domain\User\Models\User;
use Illuminate\Support\Collection;

class RecordWordPlaysAction
{
    /** @return Collection<int, array{word: string, is_first_play: bool, is_new_for_user: bool}> */
    public function execute(User $user, Move $move, Game $game): Collection
    {
        $words = collect($move->words ?? []);

        if ($words->isEmpty()) {
            return collect();
        }

        $dictionaries = $this->getDictionaries($words, $game->language);
        $existingPlays = $this->getExistingPlays($user, $dictionaries);

        $results = $words
            ->map(fn (string $word) => $this->processWord($word, $user, $dictionaries, $existingPlays))
            ->filter();

        $this->saveNewPlays($results, $user);

        return $results->map(fn (array $result): array => [
            'word' => (string) $result['word'],
            'is_first_play' => (bool) $result['is_first_play'],
            'is_new_for_user' => (bool) $result['is_new_for_user'],
        ])->values();
    }

    private function getDictionaries(Collection $words, string $language): Collection
    {
        return Dictionary::where('language', $language)
            ->whereIn('word', $words->map(fn (string $word): string => strtoupper($word)))
            ->get()
            ->keyBy('word');
    }

    private function getExistingPlays(User $user, Collection $dictionaries): Collection
    {
        return UserWordPlay::where('user_id', $user->id)
            ->whereIn('dictionary_id', $dictionaries->pluck('id'))
            ->get()
            ->keyBy('dictionary_id');
    }

    private function processWord(string $word, User $user, Collection $dictionaries, Collection $existingPlays): ?array
    {
        $uppercaseWord = strtoupper($word);
        $dictionary = $dictionaries->get($uppercaseWord);

        if (! $dictionary) {
            return null;
        }

        $isFirstGlobalPlay = $dictionary->first_played_by_user_id === null;
        $existingPlay = $existingPlays->get($dictionary->id);
        $isNewForUser = $existingPlay === null;

        if ($isFirstGlobalPlay) {
            $dictionary->update(['first_played_by_user_id' => $user->id]);
        }

        if (! $isNewForUser) {
            $existingPlay->increment('times_played');
        }

        return [
            'word' => $uppercaseWord,
            'is_first_play' => $isFirstGlobalPlay,
            'is_new_for_user' => $isNewForUser,
            'dictionary_id' => $dictionary->id,
        ];
    }

    private function saveNewPlays(Collection $results, User $user): void
    {
        $newPlays = $results->filter(fn (array $result) => $result['is_new_for_user']);

        if ($newPlays->isEmpty()) {
            return;
        }

        $records = $newPlays->map(fn (array $result) => [
            'user_id' => $user->id,
            'dictionary_id' => $result['dictionary_id'],
            'times_played' => 1,
            'first_played_at' => now(),
        ])->all();

        UserWordPlay::insert($records);

        $user->getOrCreateStatistics()->increment('unique_words_played', $newPlays->count());
    }
}
