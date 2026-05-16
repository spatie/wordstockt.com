<?php

use App\Domain\Support\Actions\RejectWordAdditionAction;
use App\Domain\Support\Models\Dictionary;
use App\Domain\User\Models\User;
use App\Mail\WordRejectedMail;
use Illuminate\Support\Facades\Mail;

it('emails the requester and clears the pending request when rejected', function (): void {
    Mail::fake();

    $requester = User::factory()->create();

    Dictionary::create([
        'language' => 'nl',
        'word' => 'TESTWOORD',
        'is_valid' => false,
        'requested_by_user_id' => $requester->id,
    ]);

    app(RejectWordAdditionAction::class)->execute('TESTWOORD', 'nl');

    Mail::assertSent(WordRejectedMail::class, function (WordRejectedMail $mail) use ($requester) {
        return $mail->hasTo($requester->email)
            && $mail->word === 'TESTWOORD'
            && $mail->language === 'nl';
    });

    $dictionary = Dictionary::where('language', 'nl')->where('word', 'TESTWOORD')->first();

    expect($dictionary->requested_by_user_id)->toBeNull();
    expect($dictionary->is_valid)->toBeFalse();
    expect(Dictionary::where('word', 'TESTWOORD')->count())->toBe(1);
});

it('does not send mail when the row has no requester', function (): void {
    Mail::fake();

    Dictionary::create([
        'language' => 'nl',
        'word' => 'GEENREQUEST',
        'is_valid' => false,
    ]);

    app(RejectWordAdditionAction::class)->execute('GEENREQUEST', 'nl');

    Mail::assertNotSent(WordRejectedMail::class);
});

it('does nothing and does not error when no row exists', function (): void {
    Mail::fake();

    app(RejectWordAdditionAction::class)->execute('ONBEKEND', 'nl');

    Mail::assertNotSent(WordRejectedMail::class);
    expect(Dictionary::where('word', 'ONBEKEND')->exists())->toBeFalse();
});
