<?php

use App\Domain\Support\Actions\GenerateWordRecommendationAction;
use App\Domain\Support\Agents\WordValidityAgent;
use App\Domain\Support\Data\WordDefinitionData;
use App\Domain\Support\Enums\DictionaryLanguage;
use App\Domain\Support\Enums\WordRecommendation;
use App\Domain\Support\Models\Dictionary;
use Laravel\Ai\Prompts\AgentPrompt;

it('maps the structured response onto a recommendation', function (): void {
    WordValidityAgent::fake([[
        'recommendation' => 'add',
        'confidence' => 92,
        'reasoning' => "  Wenen is de infinitief van het werkwoord wenen.  \n",
    ]]);

    $recommendation = generateRecommendationFor('WENEN');

    expect($recommendation->recommendation)->toBe(WordRecommendation::Add)
        ->and($recommendation->confidence)->toBe(92)
        ->and($recommendation->reasoning)->toBe('Wenen is de infinitief van het werkwoord wenen.');
});

it('asks about the word that was requested', function (): void {
    WordValidityAgent::fake();

    generateRecommendationFor('WENEN');

    WordValidityAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('Word: WENEN'));
});

it('grounds the prompt with the senses already known for the word', function (): void {
    WordValidityAgent::fake();

    Dictionary::create([
        'language' => 'nl',
        'word' => 'WENEN',
        'is_valid' => false,
        'definition' => (new WordDefinitionData(senses: [
            ['definition' => 'huilen, tranen vergieten', 'pos' => 'Werkwoord'],
            ['definition' => 'hoofdstad van Oostenrijk', 'pos' => 'Eigennaam'],
        ]))->toJson(),
    ]);

    generateRecommendationFor('WENEN');

    WordValidityAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('- Werkwoord: huilen, tranen vergieten'));
    WordValidityAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('- Eigennaam: hoofdstad van Oostenrijk'));
});

it('omits the senses block when nothing is known about the word', function (): void {
    WordValidityAgent::fake();

    generateRecommendationFor('WENEN');

    WordValidityAgent::assertNotPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('A Wiktionary dump lists these senses'));
});

it('names the reference works of the language it is asked about', function (): void {
    expect((new WordValidityAgent(DictionaryLanguage::Dutch))->instructions())
        ->toContain('Officiële woordenlijst voor Scrabble (SWL)')
        ->not->toContain('Collins Scrabble Words');

    expect((new WordValidityAgent(DictionaryLanguage::English))->instructions())
        ->toContain('Collins Scrabble Words (CSW24')
        ->not->toContain('Groene Boekje');
});

function generateRecommendationFor(string $word, DictionaryLanguage $language = DictionaryLanguage::Dutch)
{
    return app(GenerateWordRecommendationAction::class)->execute($word, $language);
}
