<?php

use App\Domain\Support\Data\WordRecommendationData;
use App\Domain\Support\Enums\WordRecommendation;
use App\Domain\User\Models\User;
use App\Mail\WordRequestedMail;

it('renders the recommendation in its own colour', function (WordRecommendation $verdict, string $accentColor, string $label): void {
    $html = renderWordRequestedMail(new WordRecommendationData($verdict, 92, 'Omdat het een werkwoord is.'));

    expect($html)
        ->toContain($accentColor)
        ->toContain($label)
        ->toContain('92% confident')
        ->toContain('Omdat het een werkwoord is.');
})->with([
    [WordRecommendation::Add, '#16A34A', 'AI recommends: ADD THIS WORD'],
    [WordRecommendation::Reject, '#DC2626', 'AI recommends: REJECT THIS WORD'],
    [WordRecommendation::Uncertain, '#D97706', 'AI is unsure: CHECK MANUALLY'],
]);

it('renders each paragraph of the reasoning separately', function (): void {
    $html = renderWordRequestedMail(new WordRecommendationData(
        WordRecommendation::Add,
        92,
        "Eerste alinea.\n\nTweede alinea.",
    ));

    expect($html)
        ->toContain('Eerste alinea.</p>')
        ->toContain('Tweede alinea.</p>');
});

it('renders no recommendation block when there is no recommendation', function (): void {
    expect(renderWordRequestedMail(null))
        ->not->toContain('AI recommends')
        ->not->toContain('woordenlijst.org')
        ->toContain('Add Word')
        ->toContain('Reject Word');
});

it('shows the independent Dutch evidence with a link to the submitted word', function (string $evidence): void {
    $html = renderWordRequestedMail(new WordRecommendationData(WordRecommendation::Uncertain, 40, 'Controleer de SWL.', $evidence));

    expect($html)
        ->toContain('https://woordenlijst.org/zoeken/?q=wenen');

    expect(html_entity_decode(strip_tags($html)))->toContain($evidence);
})->with([
    'Gevonden: "wenen" — infinitief van "wenen" (hoofdwerkwoord).',
    'Geen exacte woordvorm gevonden; dit bewijst niet dat het woord ongeldig is.',
    'Opzoeken niet gelukt; controleer de bron handmatig.',
]);

it('escapes both model output and external dictionary evidence', function (): void {
    $html = renderWordRequestedMail(new WordRecommendationData(
        WordRecommendation::Add,
        95,
        '<script>alert("model")</script>',
        '<img src=x onerror=alert("source")>',
    ));

    expect($html)
        ->not->toContain('<script>')
        ->not->toContain('<img src=x')
        ->toContain('&lt;script&gt;')
        ->toContain('&lt;img');
});

it('keeps lengthy source results compact while leaving the source link available', function (): void {
    $html = renderWordRequestedMail(new WordRecommendationData(WordRecommendation::Add, 95, 'Geldig woord.', str_repeat('woord ', 100)));

    expect($html)
        ->toContain('https://woordenlijst.org/zoeken/?q=wenen')
        ->not->toContain(str_repeat('woord ', 100));
});

function renderWordRequestedMail(?WordRecommendationData $recommendation): string
{
    return (new WordRequestedMail(
        word: 'WENEN',
        language: 'nl',
        requester: User::factory()->create(),
        recommendation: $recommendation,
    ))->render();
}
