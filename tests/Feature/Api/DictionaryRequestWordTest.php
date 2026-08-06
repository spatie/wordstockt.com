<?php

use App\Domain\Support\Enums\DictionaryLanguage;
use App\Domain\Support\Models\Dictionary;
use App\Domain\User\Models\User;
use App\Jobs\SendWordRequestedMailJob;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;

it('can request a word addition', function (): void {
    Queue::fake();

    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/dictionary/request', [
        'word' => 'nieuwwoord',
        'language' => 'nl',
    ]);

    $response->assertNoContent();

    Queue::assertPushed(SendWordRequestedMailJob::class, function (SendWordRequestedMailJob $job) use ($user) {
        return $job->word === 'NIEUWWOORD'
            && $job->language === DictionaryLanguage::Dutch
            && $job->requester->is($user);
    });
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
