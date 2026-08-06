<?php

use App\Domain\Support\Agents\WordValidityAgent;
use App\Domain\Support\Enums\DictionaryLanguage;
use App\Domain\Support\Enums\WordRecommendation;
use App\Domain\User\Models\User;
use App\Jobs\SendWordRequestedMailJob;
use App\Mail\WordRequestedMail;
use Illuminate\Support\Facades\Mail;

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

        return $mail->hasTo('freek@spatie.be');
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

function jobFor(string $word): SendWordRequestedMailJob
{
    return new SendWordRequestedMailJob($word, DictionaryLanguage::Dutch, User::factory()->create());
}
