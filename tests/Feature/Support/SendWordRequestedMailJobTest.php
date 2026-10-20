<?php

use App\Domain\Support\Actions\GenerateWordRecommendationAction;
use App\Domain\Support\Agents\WordValidityAgent;
use App\Domain\Support\Enums\DictionaryLanguage;
use App\Domain\Support\Enums\WordRecommendation;
use App\Domain\User\Models\User;
use App\Jobs\SendWordRequestedMailJob;
use App\Mail\WordRequestedMail;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

beforeEach(function (): void {
    Http::preventStrayRequests();
    Http::fake(['woordenlijst.org/*' => Http::response('', 503)]);
});

it('sends the mail with the recommendation attached', function (): void {
    Mail::fake();

    WordValidityAgent::fake([[
        'recommendation' => 'reject',
        'confidence' => 88,
        'reasoning' => 'Amsterdam is uitsluitend de naam van een stad.',
    ]]);

    SendWordRequestedMailJob::dispatchSync('AMSTERDAM', DictionaryLanguage::Dutch, User::factory()->create());

    Mail::assertSent(WordRequestedMail::class, function (WordRequestedMail $mail): bool {
        expect($mail->recommendation->recommendation)->toBe(WordRecommendation::Reject);
        expect($mail->recommendation->confidence)->toBe(88);
        expect($mail->recommendation->reasoning)->toBe('Amsterdam is uitsluitend de naam van een stad.');
        expect($mail->recommendation->woordenlijstEvidence)->toContain('Opzoeken niet gelukt');

        return $mail->hasTo('freek@spatie.be');
    });
});

it('still sends the mail when the hourly recommendation limit is reached', function (): void {
    Mail::fake();
    WordValidityAgent::fake();

    foreach (range(1, 20) as $ignored) {
        app(GenerateWordRecommendationAction::class)->execute('OPGEBRUIKT', DictionaryLanguage::Dutch);
    }

    SendWordRequestedMailJob::dispatchSync('AMSTERDAM', DictionaryLanguage::Dutch, User::factory()->create());

    Mail::assertSent(WordRequestedMail::class, function (WordRequestedMail $mail): bool {
        expect($mail->recommendation)->toBeNull();

        return $mail->word === 'AMSTERDAM';
    });
});

it('still sends the mail once generating a recommendation has failed for good', function (): void {
    Mail::fake();

    jobFor('AMSTERDAM')->failed(new RuntimeException('The OpenAI API is down.'));

    Mail::assertSent(WordRequestedMail::class, function (WordRequestedMail $mail): bool {
        expect($mail->recommendation)->toBeNull();

        return $mail->word === 'AMSTERDAM';
    });
});

it('retries a few times before giving up', function (): void {
    expect(jobFor('AMSTERDAM')->tries)->toBe(3);
});

it('retries AI failures without sending an incomplete assessment', function (): void {
    Mail::fake();
    WordValidityAgent::fake(fn () => throw new RuntimeException('The OpenAI API is down.'));
    $job = jobFor('ZEREN');

    expect(fn () => $job->handle(app(GenerateWordRecommendationAction::class)))
        ->toThrow(RuntimeException::class, 'The OpenAI API is down.');

    Mail::assertNothingSent();

    $job->failed(new RuntimeException('Retries exhausted.'));

    Mail::assertSent(WordRequestedMail::class, fn (WordRequestedMail $mail): bool => $mail->recommendation === null
        && $mail->word === 'ZEREN'
        && $mail->hasTo('freek@spatie.be'));
    Mail::assertSentCount(1);
});

it('falls back to the review email after unusable model output exhausts retries', function (): void {
    Mail::fake();
    WordValidityAgent::fake([['recommendation' => 'add', 'confidence' => 101, 'reasoning' => 'Onbetrouwbaar.']]);
    $job = jobFor('ZEREN');

    expect(fn () => $job->handle(app(GenerateWordRecommendationAction::class)))
        ->toThrow(ValidationException::class);

    Mail::assertNothingSent();

    $job->failed(new RuntimeException('Retries exhausted.'));

    Mail::assertSent(WordRequestedMail::class, fn (WordRequestedMail $mail): bool => $mail->recommendation === null);
    Mail::assertSentCount(1);
});

function jobFor(string $word): SendWordRequestedMailJob
{
    return new SendWordRequestedMailJob($word, DictionaryLanguage::Dutch, User::factory()->create());
}
