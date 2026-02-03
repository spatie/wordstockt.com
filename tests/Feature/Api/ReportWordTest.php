<?php

use App\Domain\Support\Models\Dictionary;
use App\Domain\User\Models\User;
use App\Mail\WordReportedMail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    Dictionary::create(['language' => 'nl', 'word' => 'TEST']);
    Dictionary::create(['language' => 'en', 'word' => 'HELLO']);
});

it('can report a valid word', function (): void {
    Carbon::setTestNow('2026-01-20 14:00:00');

    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/dictionary/report', [
        'word' => 'TEST',
        'language' => 'nl',
    ]);

    $response->assertNoContent();

    $dictionary = Dictionary::where('language', 'nl')->where('word', 'TEST')->first();

    expect($dictionary->requested_to_mark_as_invalid_at->toDateTimeString())->toBe('2026-01-20 14:00:00');
});

it('normalizes word to uppercase', function (): void {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/dictionary/report', [
        'word' => 'test',
        'language' => 'nl',
    ]);

    $response->assertNoContent();

    $dictionary = Dictionary::where('language', 'nl')->where('word', 'TEST')->first();

    expect($dictionary->requested_to_mark_as_invalid_at)->not->toBeNull();
});

it('returns 404 for non-existent word', function (): void {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/dictionary/report', [
        'word' => 'NONEXISTENT',
        'language' => 'nl',
    ]);

    $response->assertNotFound();
});

it('returns 404 for already invalid word', function (): void {
    Dictionary::where('language', 'nl')->where('word', 'TEST')->update(['is_valid' => false]);

    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/dictionary/report', [
        'word' => 'TEST',
        'language' => 'nl',
    ]);

    $response->assertNotFound();
});

it('requires authentication', function (): void {
    $response = $this->postJson('/api/dictionary/report', [
        'word' => 'TEST',
        'language' => 'nl',
    ]);

    $response->assertUnauthorized();
});

it('validates required word', function (): void {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/dictionary/report', [
        'language' => 'nl',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['word']);
});

it('validates required language', function (): void {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/dictionary/report', [
        'word' => 'TEST',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['language']);
});

it('validates language must be nl or en', function (): void {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/dictionary/report', [
        'word' => 'TEST',
        'language' => 'de',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['language']);
});

it('validates word minimum length', function (): void {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/dictionary/report', [
        'word' => 'A',
        'language' => 'nl',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['word']);
});

it('sends email when word is reported', function (): void {
    Mail::fake();

    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/dictionary/report', [
        'word' => 'TEST',
        'language' => 'nl',
    ]);

    Mail::assertSent(WordReportedMail::class, function (WordReportedMail $mail) use ($user) {
        return $mail->hasTo('freek@spatie.be')
            && $mail->dictionary->word === 'TEST'
            && $mail->dictionary->language === 'nl'
            && $mail->reporter->is($user);
    });
});

it('does not send email when word was already reported', function (): void {
    Mail::fake();

    Dictionary::where('language', 'nl')->where('word', 'TEST')->update([
        'requested_to_mark_as_invalid_at' => now(),
    ]);

    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/dictionary/report', [
        'word' => 'TEST',
        'language' => 'nl',
    ]);

    Mail::assertNotSent(WordReportedMail::class);
});
