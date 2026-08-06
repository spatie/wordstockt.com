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
    expect(renderWordRequestedMail(null))->not->toContain('AI recommends');
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
