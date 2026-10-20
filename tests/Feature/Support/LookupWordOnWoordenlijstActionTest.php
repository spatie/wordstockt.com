<?php

use App\Domain\Support\Actions\LookupWordOnWoordenlijstAction;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    Http::preventStrayRequests();
});

it('looks up the exact lowercase form using the live sites wordform endpoint', function (): void {
    Http::fake(['woordenlijst.org/*' => Http::response(zerenLookupXml())]);

    expect(app(LookupWordOnWoordenlijstAction::class)->execute(' ZEREN '))
        ->toBe('Gevonden: "zeren" — meervoud van "zeer" (zelfstandig naamwoord (o)).');

    Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
        && str_starts_with($request->url(), 'https://woordenlijst.org/MolexServe/lexicon/find_wordform?')
        && $request['wordform'] === 'zeren'
        && $request['database'] === 'gig_pro_wrdlst'
        && $request['paradigm'] === 'true'
        && $request['onlyvalid'] === 'true'
        && $request['regex'] === 'false');
});

it('does not mistake the query echo or a related form for an exact match', function (): void {
    $xml = str_replace('<wordform>zeren</wordform><label>', '<wordform>zeertjes</wordform><label>', zerenLookupXml());
    Http::fake(['woordenlijst.org/*' => Http::response($xml)]);

    expect(app(LookupWordOnWoordenlijstAction::class)->execute('ZEREN'))
        ->toBe('Geen exacte woordvorm gevonden; dit bewijst niet dat het woord ongeldig is.');
});

it('retains proper noun and trademark evidence rather than treating every hit as playable', function (): void {
    $xml = str_replace(
        ['<lemma>zeer</lemma>', 'zelfstandig naamwoord (o)', '<trademark>false</trademark>'],
        ['<lemma>Zeren</lemma>', 'naam (o)', '<trademark>true</trademark>'],
        zerenLookupXml(),
    );
    Http::fake(['woordenlijst.org/*' => Http::response($xml)]);

    expect(app(LookupWordOnWoordenlijstAction::class)->execute('ZEREN'))
        ->toContain('"Zeren" (naam (o); handelsmerk)');
});

it('keeps distinct senses but deduplicates identical matching inflections', function (): void {
    $xml = simplexml_load_string(zerenLookupXml());
    $entry = $xml->found_lemmata->asXML();
    $otherSense = str_replace(['<lemma>zeer</lemma>', 'zelfstandig naamwoord (o)'], ['<lemma>zeren</lemma>', 'hoofdwerkwoord'], $entry);
    Http::fake(['woordenlijst.org/*' => Http::response(str_replace('</root>', $entry.$otherSense.'</root>', zerenLookupXml()))]);

    $evidence = app(LookupWordOnWoordenlijstAction::class)->execute('ZEREN');

    expect(substr_count($evidence, 'van "zeer"'))->toBe(1)
        ->and($evidence)->toContain('van "zeren" (hoofdwerkwoord)');
});

it('distinguishes an empty successful lookup from a service failure', function (): void {
    Http::fake(['woordenlijst.org/*' => Http::response('<root><wordform>zeren</wordform><message>OK</message></root>')]);

    expect(app(LookupWordOnWoordenlijstAction::class)->execute('ZEREN'))
        ->toStartWith('Geen exacte woordvorm gevonden');
});

it('treats unavailable or unrecognised responses as unavailable rather than not found', function (string $body, int $status): void {
    Http::fake(['woordenlijst.org/*' => Http::response($body, $status)]);

    expect(app(LookupWordOnWoordenlijstAction::class)->execute('ZEREN'))
        ->toBe('Opzoeken niet gelukt; controleer de bron handmatig.');
})->with([
    'server error' => ['Service unavailable', 503],
    'not found endpoint' => ['', 404],
    'empty response' => ['', 200],
    'malformed XML' => ['<root><wordform>', 200],
    'HTML challenge' => ['<html><body>Enable JavaScript</body></html>', 200],
    'unexpected XML' => ['<results><message>OK</message></results>', 200],
    'API error' => ['<root><wordform>zeren</wordform><message>ERROR</message></root>', 200],
    'wrong query' => ['<root><wordform>zeer</wordform><message>OK</message></root>', 200],
    'incomplete entry' => ['<root><wordform>zeren</wordform><found_lemmata/><message>OK</message></root>', 200],
    'external entity' => ['<!DOCTYPE root [<!ENTITY secret SYSTEM "file:///etc/hostname">]><root><wordform>zeren</wordform><message>OK</message><found_lemmata>&secret;</found_lemmata></root>', 200],
]);

it('handles connection failures without losing the assessment', function (): void {
    Http::fake(['woordenlijst.org/*' => Http::failedConnection()]);

    expect(app(LookupWordOnWoordenlijstAction::class)->execute('ZEREN'))
        ->toBe('Opzoeken niet gelukt; controleer de bron handmatig.');
});

it('never turns input into a wildcard or regex search', function (string $word): void {
    expect(app(LookupWordOnWoordenlijstAction::class)->execute($word))
        ->toBe('Opzoeken niet gelukt; controleer de bron handmatig.');

    Http::assertNothingSent();
})->with(['ze*', 'ze%', 'ze_', 'ze?', 'zee/ren']);

function zerenLookupXml(): string
{
    return <<<'XML'
    <?xml version="1.0" encoding="UTF-8"?>
    <root>
        <wordform>zeren</wordform>
        <found_lemmata>
            <lemma>zeer</lemma>
            <label>zelfstandig naamwoord (o)</label>
            <trademark>false</trademark>
            <gloss/>
            <paradigm>
                <paradigm><wordform>zeer</wordform><label>enkelvoud</label></paradigm>
                <paradigm><wordform>zeren</wordform><label>meervoud</label></paradigm>
            </paradigm>
            <message>OK</message>
        </found_lemmata>
        <message>OK</message>
    </root>
    XML;
}
