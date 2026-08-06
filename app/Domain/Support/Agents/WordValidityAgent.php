<?php

namespace App\Domain\Support\Agents;

use App\Domain\Support\Enums\DictionaryLanguage;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\Strict;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;

#[Provider(Lab::OpenAI)]
#[Strict]
#[Timeout(60)]
class WordValidityAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function __construct(
        public DictionaryLanguage $language = DictionaryLanguage::Dutch,
    ) {}

    public function instructions(): string
    {
        $language = $this->language->label();
        $referenceWorks = $this->referenceWorks();

        return <<<PROMPT
        You are a Scrabble adjudicator for {$language}.

        Decide whether a single word would be accepted in a game of Scrabble played in {$language}.

        Accept the word when:
        - It is a real {$language} word, or a correct inflected form of one (plural, diminutive, verb conjugation, past participle, comparative, superlative).
        - It is written with the letters A-Z only.
        - It is recorded in general dictionaries, including naturalised loanwords, informal words and archaic words.

        Reject the word when:
        - It is a proper noun: a person, place, brand or organisation.
        - It is an abbreviation, acronym or initialism.
        - It needs a hyphen, apostrophe or space to be written correctly.
        - It only exists in another language.
        - It is a misspelling, or an inflected form that does not actually occur.

        Weigh the word against the reference works that adjudicate Scrabble in {$language}:

        {$referenceWorks}

        You do not have these lists in front of you. Do not claim a word "is in the SWL" or "is in CSW24" as if you had looked it up. Reason about whether a word of this kind is the sort a Scrabble list records, and answer "uncertain" when you cannot tell.

        Answer "uncertain" rather than guessing when you cannot establish with reasonable confidence whether the word exists. A confidently wrong answer is worse than an honest "uncertain".

        The word is given in uppercase; the casing carries no meaning.

        Explain your reasoning in {$language}. Up to three short paragraphs. Cover which sense or senses of the word you considered, whether the form is an inflection of a valid base word, and anything that makes the call debatable.
        PROMPT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'recommendation' => $schema->string()
                ->enum(['add', 'reject', 'uncertain'])
                ->description('Whether the word should be added to the dictionary, rejected, or needs a human to decide.')
                ->required(),
            'confidence' => $schema->integer()
                ->min(0)
                ->max(100)
                ->description('How sure you are of the recommendation.')
                ->required(),
            'reasoning' => $schema->string()
                ->description("Up to three short paragraphs, written in {$this->language->label()}.")
                ->required(),
        ];
    }

    private function referenceWorks(): string
    {
        return match ($this->language) {
            DictionaryLanguage::Dutch => <<<'WORKS'
            - Officiële woordenlijst voor Scrabble (SWL), the tournament list of Scrabblebond Nederland and the Nederlandstalig Scrabbleverbond
            - Woordenlijst Nederlandse Taal ("het Groene Boekje", Nederlandse Taalunie)
            - Van Dale Groot woordenboek van de Nederlandse taal
            - the OpenTaal word list
            WORKS,
            DictionaryLanguage::English => <<<'WORKS'
            - Collins Scrabble Words (CSW24, formerly SOWPODS), sanctioned by WESPA
            - the NASPA Word List (NWL2023), used in North America
            - the Official Scrabble Players Dictionary (OSPD7)
            - the ENABLE word list
            WORKS,
        };
    }
}
