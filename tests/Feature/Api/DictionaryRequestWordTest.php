<?php

use App\Domain\Support\Models\Dictionary;
use App\Domain\User\Models\User;
use App\Mail\WordRequestedMail;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;

it('can request a word addition', function (): void {
    Mail::fake();

    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/dictionary/request', [
        'word' => 'nieuwwoord',
        'language' => 'nl',
    ]);

    $response->assertNoContent();

    Mail::assertSent(WordRequestedMail::class, function (WordRequestedMail $mail) use ($user) {
        return $mail->hasTo('freek@spatie.be')
            && $mail->word === 'NIEUWWOORD'
            && $mail->language === 'nl'
            && $mail->requester->is($user);
    });
});

it('does not send email if word already exists', function (): void {
    Mail::fake();

    Dictionary::create(['language' => 'nl', 'word' => 'BESTAAND', 'is_valid' => true]);

    Sanctum::actingAs(User::factory()->create());

    $response = $this->postJson('/api/dictionary/request', [
        'word' => 'bestaand',
        'language' => 'nl',
    ]);

    $response->assertNoContent();

    Mail::assertNotSent(WordRequestedMail::class);
});

it('requires authentication', function (): void {
    $response = $this->postJson('/api/dictionary/request', [
        'word' => 'test',
        'language' => 'nl',
    ]);

    $response->assertUnauthorized();
});

it('validates required word', function (): void {
    Sanctum::actingAs(User::factory()->create());

    $response = $this->postJson('/api/dictionary/request', [
        'language' => 'nl',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['word']);
});

it('validates word must be alpha', function (): void {
    Sanctum::actingAs(User::factory()->create());

    $response = $this->postJson('/api/dictionary/request', [
        'word' => 'test123',
        'language' => 'nl',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['word']);
});

it('validates language must be nl or en', function (): void {
    Sanctum::actingAs(User::factory()->create());

    $response = $this->postJson('/api/dictionary/request', [
        'word' => 'test',
        'language' => 'de',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['language']);
});
