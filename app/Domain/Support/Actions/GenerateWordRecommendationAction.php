<?php

namespace App\Domain\Support\Actions;

use App\Domain\Support\Agents\WordValidityAgent;
use App\Domain\Support\Data\WordRecommendationData;
use App\Domain\Support\Enums\DictionaryLanguage;
use App\Domain\Support\Models\Dictionary;
use Laravel\Ai\Responses\StructuredAgentResponse;
use RuntimeException;

class GenerateWordRecommendationAction
{
    public function execute(string $word, DictionaryLanguage $language): WordRecommendationData
    {
        $response = (new WordValidityAgent($language))->prompt($this->buildPrompt($word, $language));

        if (! $response instanceof StructuredAgentResponse) {
            throw new RuntimeException("The word validity agent returned no structured verdict for [{$word}].");
        }

        return WordRecommendationData::fromStructuredResponse($response->toArray());
    }

    private function buildPrompt(string $word, DictionaryLanguage $language): string
    {
        $knownSenses = $this->knownSenses($word, $language);

        if ($knownSenses === '') {
            return "Word: {$word}";
        }

        return <<<PROMPT
        Word: {$word}

        A Wiktionary dump lists these senses for this word:
        {$knownSenses}

        Treat these as evidence, not as a verdict. A word is only a proper noun if *all* of its senses are proper nouns.
        PROMPT;
    }

    private function knownSenses(string $word, DictionaryLanguage $language): string
    {
        $dictionary = Dictionary::query()
            ->where('language', $language->value)
            ->where('word', $word)
            ->first();

        if (! $dictionary) {
            return '';
        }

        return collect($dictionary->getDefinitionData()->senses ?? [])
            ->filter(fn (array $sense): bool => filled($sense['definition'] ?? null))
            ->map(fn (array $sense): string => $this->describeSense($sense))
            ->implode("\n");
    }

    /** @param array{definition: string, pos?: ?string} $sense */
    private function describeSense(array $sense): string
    {
        if (blank($sense['pos'] ?? null)) {
            return "- {$sense['definition']}";
        }

        return "- {$sense['pos']}: {$sense['definition']}";
    }
}
