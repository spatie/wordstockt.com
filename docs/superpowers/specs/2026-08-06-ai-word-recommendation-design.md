# AI recommendation for requested words

Date: 2026-08-06

## Problem

When a player requests a word to be added to the dictionary, an email arrives at
freek@spatie.be with an **Add Word** and a **Reject Word** button. Deciding
between them means looking the word up by hand, which is the slow part. Most
requests are for ordinary words or ordinary inflections, and a small number are
proper nouns, abbreviations or typos.

We want an AI recommendation rendered in that same email, underneath the two
buttons, so the decision can usually be made without leaving the inbox.

## Scope

In scope:

- A `laravel/ai` agent that judges whether a word is a valid Scrabble word in
  Dutch or English.
- Queued generation of the recommendation, then sending the existing email with
  the recommendation attached.
- A visually distinct recommendation block in the email.
- Upgrading the test suite from Pest 4 to Pest 5.
- Evals for the agent using `pestphp/pest-plugin-evals`.

Out of scope:

- Persisting the recommendation. It lives only in the email.
- Acting on the recommendation automatically. A human still clicks a button.
- The word *report* flow (`WordReportedMail`), which stays as it is.

## Architecture

```
RequestWordAdditionAction
        │  dispatch
        ▼
SendWordRequestedMailJob  (tries = 3)
        │
        ├─ GenerateWordRecommendationAction ──► WordValidityAgent  (OpenAI)
        │        │                                      │ structured output
        │        ▼                                      ▼
        │   WordRecommendationData { recommendation, confidence, reasoning }
        │
        └─ Mail::to('freek@spatie.be')->send(new WordRequestedMail(…, $recommendation))

failed()  ──► send the same mail with $recommendation = null
```

`RequestWordAdditionAction` keeps its existing early return when the word is
already valid, and keeps stamping `requested_by_user_id`. The only change is
that it dispatches a job instead of sending mail inline, so the API request no
longer waits on OpenAI.

### Components

| Component | Responsibility | Depends on |
|---|---|---|
| `App\Domain\Support\Enums\WordRecommendation` | The three possible verdicts, plus their label, colour and icon | – |
| `App\Domain\Support\Data\WordRecommendationData` | Readonly value object carrying verdict, confidence and reasoning | the enum |
| `App\Ai\Agents\WordValidityAgent` | Instructions, model configuration and output schema | `laravel/ai`, `DictionaryLanguage` |
| `App\Domain\Support\Actions\GenerateWordRecommendationAction` | Builds the user message, grounds it with known Wiktionary senses, calls the agent, maps the response to the DTO | the agent, `Dictionary` |
| `App\Jobs\SendWordRequestedMailJob` | Orchestrates generate-then-send, and degrades to send-without on repeated failure | the action, `WordRequestedMail` |
| `resources/views/emails/partials/word-recommendation.blade.php` | Renders the block, formatting from the enum only | the enum |

Each of these can be understood and tested on its own. The action never touches
mail, the job never touches the model, and the Blade partial never sees raw
model output for anything other than the reasoning text.

## The agent

### Model configuration

```php
#[Provider(Lab::OpenAI)]
#[Temperature(0.2)]
#[Timeout(30)]
```

Low temperature because we want the same word to get the same verdict.

### Instructions

Parameterised by `DictionaryLanguage`, where `{LANGUAGE}` is `Dutch` or
`English`.

```
You are a Scrabble adjudicator for {LANGUAGE}.

Decide whether a single word would be accepted in a game of Scrabble played
in {LANGUAGE}.

Accept the word when:
- It is a real {LANGUAGE} word, or a correct inflected form of one (plural,
  diminutive, verb conjugation, past participle, comparative, superlative).
- It is written with the letters A-Z only.
- It is recorded in general dictionaries, including naturalised loanwords,
  informal words and archaic words.

Reject the word when:
- It is a proper noun: a person, place, brand or organisation.
- It is an abbreviation, acronym or initialism.
- It needs a hyphen, apostrophe or space to be written correctly.
- It only exists in another language.
- It is a misspelling, or an inflected form that does not actually occur.

Weigh the word against the reference works that adjudicate Scrabble in
{LANGUAGE}:

{REFERENCE_WORKS}

You do not have these lists in front of you. Do not claim a word "is in the
SWL" or "is in CSW24" as if you had looked it up. Reason about whether a word
of this kind is the sort a Scrabble list records, and answer "uncertain" when
you cannot tell.

Answer "uncertain" rather than guessing when you cannot establish with
reasonable confidence whether the word exists. A confidently wrong answer is
worse than an honest "uncertain".

The word is given in uppercase; the casing carries no meaning.

Explain your reasoning in {LANGUAGE}. Up to three short paragraphs. Cover
which sense or senses of the word you considered, whether the form is an
inflection of a valid base word, and anything that makes the call debatable.
```

`{REFERENCE_WORKS}` for Dutch:

```
- Officiële woordenlijst voor Scrabble (SWL), the tournament list of
  Scrabblebond Nederland and the Nederlandstalig Scrabbleverbond
- Woordenlijst Nederlandse Taal ("het Groene Boekje", Nederlandse Taalunie)
- Van Dale Groot woordenboek van de Nederlandse taal
- the OpenTaal word list
```

`{REFERENCE_WORKS}` for English:

```
- Collins Scrabble Words (CSW24, formerly SOWPODS), sanctioned by WESPA
- the NASPA Word List (NWL2023), used in North America
- the Official Scrabble Players Dictionary (OSPD7)
- the ENABLE word list
```

The instructions are written in English while the reasoning is required in the
target language. Instruction-following is more reliable in English, and it
means one prompt parameterised by language rather than two translations kept in
sync by hand. The reasoning is the part a human reads, and that stays Dutch for
Dutch words.

Naming the reference works anchors the model to the right register: it becomes
more willing to accept obscure inflections and more consistent about rejecting
proper nouns. It also invites fabricated citations, which is why the prompt
explicitly forbids claiming list membership and routes that uncertainty to the
`uncertain` verdict instead.

### Grounding with known senses

A `dictionaries` row often already exists for a requested word, carrying a
Wiktionary `definition` JSON with senses and parts of speech. When one exists,
the user message includes it:

```
Word: WENEN

A Wiktionary dump lists these senses for this word:
- werkwoord: huilen, tranen vergieten
- eigennaam: hoofdstad van Oostenrijk

Treat these as evidence, not as a verdict. A word is only a proper noun if
*all* of its senses are proper nouns.
```

This is the single biggest accuracy lever. WENEN is the motivating case: it is
both the Dutch name for Vienna and the verb "to weep", so it is valid, and a
model asked about the bare string can easily flag it as a city name.

When no row exists, or the row has no senses, the message is just the word.

### Output schema

```php
public function schema(JsonSchema $schema): array
{
    return [
        'recommendation' => $schema->string()
            ->enum(['add', 'reject', 'uncertain'])
            ->required(),
        'confidence' => $schema->integer()->min(0)->max(100)->required(),
        'reasoning' => $schema->string()->required(),
    ];
}
```

The verdict values map one-to-one onto the two buttons in the email, so no
translation layer is needed between what the model says and what the reader
does. `WordRecommendation::from()` on the returned string means the email is
never formatted from raw model text; only `reasoning` is free-form.

## Email presentation

The block sits directly underneath the **Reject Word** button. It is a
table-based box with a 4px left accent bar, an accent-coloured header row and
the reasoning paragraphs beneath.

```
▌ ✓  AI recommends: ADD THIS WORD  ·  92% confident
▌
▌  Wenen is zowel de infinitief van het werkwoord "wenen" (huilen,
▌  tranen vergieten) als de Nederlandse naam van de Oostenrijkse
▌  hoofdstad.
▌
▌  Omdat minstens één betekenis een gewoon werkwoord is, is het geen
▌  eigennaam en telt het als geldig.
```

| Verdict | Accent | Header |
|---|---|---|
| `add` | green `#16A34A` | AI recommends: ADD THIS WORD |
| `reject` | red `#DC2626` | AI recommends: REJECT THIS WORD |
| `uncertain` | amber `#D97706` | AI is unsure — check manually |

All colours and copy come from the enum. Styles are inline, since email clients
do not reliably apply the stylesheet in `wordstockt.css` to arbitrary markup.
The reasoning is escaped and run through `nl2br` so multi-paragraph output
survives.

When `$recommendation` is `null` the partial renders nothing, and the email is
byte-for-byte what it is today.

A `/dev/mail/word-requested` route is added alongside the existing mail
previews so the block can be inspected in a browser.

## Error handling

`GenerateWordRecommendationAction` lets exceptions propagate: a missing API key,
a timeout, a rate limit or a schema violation all throw.

`SendWordRequestedMailJob` sets `$tries = 3`. If all three attempts fail,
`failed()` sends `WordRequestedMail` with `$recommendation = null`. The email
always arrives; only the recommendation is best-effort.

Consequence to accept: if the *mail send itself* fails rather than the AI call,
`failed()` will attempt the same send once more and fail again. That is
harmless, and not worth a separate code path.

## Configuration

- `composer require laravel/ai` (production dependency).
- `OPENAI_API_KEY` in `.env` and `.env.example`.
- `config/ai.php` published, `default` left at `openai`.
- Tests never call the API. `phpunit.xml` needs no AI-related entries because
  `WordValidityAgent::fake()` intercepts before any HTTP call.

## Testing

### Pest 5 upgrade

- `pestphp/pest` `^4.0` → `^5.0`
- `pestphp/pest-plugin-laravel` `^4.0` → `^5.0`
- add `pestphp/pest-plugin-evals` as a dev dependency

PHP 8.4+ is already required by this project, so the only real risk is PHPUnit
13, which Pest 5 is built on. The upgrade guide documents no Pest-level API
breaks. Verification is running the full suite and fixing what falls out.

Note: `pestphp/pest-plugin-evals` declares
`"conflict": {"laravel/ai": "<0.10.2 || >=0.11.0"}`. Installing it pins
`laravel/ai` to the 0.10.x line until the plugin loosens that constraint. This
is accepted as a known and easily reversible cost.

### Unit and feature tests

All use `WordValidityAgent::fake()`, so no network access and no API key.

- `GenerateWordRecommendationAction` maps a structured response onto
  `WordRecommendationData`.
- It includes the Wiktionary senses in the prompt when a `dictionaries` row
  exists, asserted with `WordValidityAgent::assertPrompted()`.
- It omits the senses block when no row exists.
- `SendWordRequestedMailJob` sends the mail with a recommendation attached on
  success.
- `SendWordRequestedMailJob::failed()` sends the mail with `null`.
- `RequestWordAdditionAction` dispatches the job rather than sending mail. The
  existing `DictionaryRequestWordTest`, which asserts on `Mail::fake()`, is
  updated to assert on `Queue::fake()`.
- `WordRequestedMail` renders the accent colour and header for each of the
  three verdicts, and renders no block when the recommendation is `null`.

### Evals

`tests/Evals/WordValidityAgentEval.php`, run with `./vendor/bin/pest --evals`
and skipped on a normal run so CI stays free and offline.

Five smoke cases:

| Word | Language | Expected |
|---|---|---|
| BLAFFEN | nl | `add` |
| AMSTERDAM | nl | `reject` |
| XQZPL | nl | `reject` |
| BLUSTERING | en | `add` |
| NASA | en | `reject` |

Written with the closure form of `expect()`, so the language can be passed per
eval and the assertion made against the `recommendation` field directly:

```php
expect(fn (string $word): string => recommendationFor($word, DictionaryLanguage::Dutch))
    ->prompt('BLAFFEN')
    ->toBe('add');
```

These are deterministic string assertions, so no LLM judge and no embeddings
driver are configured. If judge-based expectations are wanted later,
`pest()->evals()->judgeUsing(...)` is the hook.

## Success criteria

- Requesting a word returns a `204` without waiting on OpenAI.
- The email arrives with a colour-coded recommendation block under the buttons.
- The email still arrives, without the block, when the AI call fails three
  times.
- `./vendor/bin/pest` passes on Pest 5 with no network access.
- `./vendor/bin/pest --evals` passes the five smoke cases against OpenAI.
