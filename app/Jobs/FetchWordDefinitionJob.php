<?php

namespace App\Jobs;

use App\Domain\Support\Data\WordDefinitionData;
use App\Domain\Support\Models\Dictionary;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class FetchWordDefinitionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $word,
        public string $language,
    ) {}

    public function handle(): void
    {
        $dictionary = Dictionary::query()
            ->where('language', $this->language)
            ->where('word', $this->word)
            ->where('is_valid', true)
            ->first();

        if (! $dictionary) {
            return;
        }

        $subdomain = match ($this->language) {
            'nl' => 'nl',
            'en' => 'en',
            default => null,
        };

        if (! $subdomain) {
            return;
        }

        $lookupWord = mb_strtolower($this->word);

        $response = Http::timeout(10)
            ->get("https://{$subdomain}.wiktionary.org/api/rest_v1/page/definition/{$lookupWord}");

        if (! $response->successful()) {
            return;
        }

        $data = $response->json();
        $definitionData = $this->parseWiktionaryResponse($data);

        if ($definitionData->isEmpty()) {
            return;
        }

        $dictionary->definition = $definitionData->toJson();
        $dictionary->save();
    }

    private function parseWiktionaryResponse(array $data): WordDefinitionData
    {
        $senses = [];
        $etymology = null;

        foreach ($data as $languageSection) {
            if (! is_array($languageSection)) {
                continue;
            }

            foreach ($languageSection as $entry) {
                if (! is_array($entry)) {
                    continue;
                }

                $pos = $entry['partOfSpeech'] ?? null;

                foreach ($entry['definitions'] ?? [] as $definition) {
                    $definitionText = strip_tags($definition['definition'] ?? '');

                    if (! $definitionText) {
                        continue;
                    }

                    $examples = collect($definition['examples'] ?? [])
                        ->map(fn (string $example) => strip_tags($example))
                        ->filter()
                        ->take(2)
                        ->values()
                        ->all();

                    $senses[] = [
                        'definition' => $definitionText,
                        'pos' => $pos,
                        'examples' => $examples ?: null,
                    ];

                    if (count($senses) >= 10) {
                        break 3;
                    }
                }
            }
        }

        return new WordDefinitionData(
            senses: $senses ?: null,
            etymology: $etymology,
        );
    }
}
