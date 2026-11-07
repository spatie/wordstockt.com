<?php

use App\Domain\Support\Enums\DictionaryLanguage;
use App\Domain\Support\Models\Dictionary;
use App\Domain\User\Models\User;
use App\Jobs\SendWordRequestedMailJob;
use App\Mail\WordApprovedMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\Sanctum;

it('can request a word addition and receive its approval email', function (bool $alreadyExists): void {
    Queue::fake();
    Mail::fake();

    if ($alreadyExists) {
        Dictionary::create(['language' => 'nl', 'word' => 'NIEUWWOORD', 'is_valid' => false]);
    }

    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/dictionary/request', [
        'word' => 'nieuwwoord',
        'language' => 'nl',
    ]);

    $response->assertNoContent();

    $this->assertDatabaseHas('dictionaries', [
        'word' => 'NIEUWWOORD',
        'language' => 'nl',
        'is_valid' => false,
        'requested_by_user_id' => $user->id,
    ]);

    Queue::assertPushed(SendWordRequestedMailJob::class, function (SendWordRequestedMailJob $job) use ($user) {
        return $job->word === 'NIEUWWOORD'
            && $job->language === DictionaryLanguage::Dutch
            && $job->requester->is($user);
    });

    $this->get(URL::signedRoute('dictionary.add-word', [
        'word' => 'NIEUWWOORD',
        'language' => 'nl',
    ]))->assertOk();

    Mail::assertSent(WordApprovedMail::class, function (WordApprovedMail $mail) use ($user) {
        return $mail->hasTo($user->email)
            && $mail->word === 'NIEUWWOORD'
            && $mail->language === 'nl';
    });

    $this->assertDatabaseHas('dictionaries', [
        'word' => 'NIEUWWOORD',
        'language' => 'nl',
        'is_valid' => true,
        'requested_by_user_id' => null,
    ]);
})->with([
    'new word' => false,
    'existing invalid word' => true,
]);

it('keeps the original requester when another user requests the same word', function (): void {
    Queue::fake();

    $requester = User::factory()->create();

    Dictionary::create([
        'language' => 'nl',
        'word' => 'NIEUWWOORD',
        'is_valid' => false,
        'requested_by_user_id' => $requester->id,
    ]);

    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/dictionary/request', [
        'word' => 'nieuwwoord',
        'language' => 'nl',
    ])->assertNoContent();

    $this->assertDatabaseHas('dictionaries', [
        'word' => 'NIEUWWOORD',
        'language' => 'nl',
        'is_valid' => false,
        'requested_by_user_id' => $requester->id,
    ]);
});

it('does not send email if word already exists', function (): void {
    Queue::fake();

    Dictionary::create(['language' => 'nl', 'word' => 'BESTAAND', 'is_valid' => true]);

    Sanctum::actingAs(User::factory()->create());

    $response = $this->postJson('/api/dictionary/request', [
        'word' => 'bestaand',
        'language' => 'nl',
    ]);

    $response->assertNoContent();

    Queue::assertNotPushed(SendWordRequestedMailJob::class);
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
