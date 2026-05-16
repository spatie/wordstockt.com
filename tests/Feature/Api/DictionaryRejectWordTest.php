<?php

use App\Domain\Support\Actions\RejectWordAdditionAction;
use App\Domain\Support\Models\Dictionary;
use App\Domain\User\Models\User;
use App\Mail\WordRejectedMail;
use App\Mail\WordRequestedMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

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

it('can reject a word via signed url', function (): void {
    Mail::fake();

    $requester = User::factory()->create();

    Dictionary::create([
        'language' => 'nl',
        'word' => 'TESTWOORD',
        'is_valid' => false,
        'requested_by_user_id' => $requester->id,
    ]);

    $url = URL::signedRoute('dictionary.reject-word', [
        'word' => 'TESTWOORD',
        'language' => 'nl',
    ]);

    $response = $this->get($url);

    $response->assertOk();
    $response->assertViewIs('dictionary.action-confirmed');
    $response->assertViewHas('action', 'rejected');
    $response->assertViewHas('word', 'TESTWOORD');
    $response->assertViewHas('language', 'nl');

    Mail::assertSent(WordRejectedMail::class, fn (WordRejectedMail $mail) => $mail->hasTo($requester->email));
});

it('cannot reject a word without a valid signature', function (): void {
    $response = $this->get('/dictionary/reject-word?word=TESTWOORD&language=nl');

    $response->assertForbidden();
});

it('rejects an invalid language with 422', function (): void {
    $url = URL::signedRoute('dictionary.reject-word', [
        'word' => 'TESTWOORD',
        'language' => 'de',
    ]);

    $this->get($url)->assertStatus(422);
});

it('rejects a word shorter than two characters with 422', function (): void {
    $url = URL::signedRoute('dictionary.reject-word', [
        'word' => 'A',
        'language' => 'nl',
    ]);

    $this->get($url)->assertStatus(422);
});

it('shows the rejected confirmation page', function (): void {
    Mail::fake();

    $url = URL::signedRoute('dictionary.reject-word', [
        'word' => 'ONBEKEND',
        'language' => 'nl',
    ]);

    $response = $this->get($url);

    $response->assertOk();
    $response->assertSee('Word Rejected');
    $response->assertSee('ONBEKEND');
});

it('renders a reject button in the admin request mail', function (): void {
    $requester = User::factory()->create();

    $rendered = (new WordRequestedMail('TESTWOORD', 'nl', $requester))->render();

    expect($rendered)->toContain('/dictionary/reject-word');
    expect($rendered)->toContain('Reject Word');
});
