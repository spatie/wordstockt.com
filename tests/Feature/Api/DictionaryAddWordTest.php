<?php

use App\Domain\Support\Models\Dictionary;
use App\Jobs\FetchWordDefinitionJob;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\URL;

it('can add a word via signed url', function (): void {
    Queue::fake();

    $url = URL::signedRoute('dictionary.add-word', [
        'word' => 'NIEUW',
        'language' => 'nl',
    ]);

    $response = $this->get($url);

    $response->assertOk();
    $response->assertViewIs('dictionary.action-confirmed');
    $response->assertViewHas('action', 'added');
    $response->assertViewHas('word', 'NIEUW');
    $response->assertViewHas('language', 'nl');

    expect(Dictionary::where('language', 'nl')->where('word', 'NIEUW')->where('is_valid', true)->exists())->toBeTrue();

    Queue::assertPushed(FetchWordDefinitionJob::class, function (FetchWordDefinitionJob $job) {
        return $job->word === 'NIEUW' && $job->language === 'nl';
    });
});

it('is idempotent when word already exists', function (): void {
    Queue::fake();

    Dictionary::create(['language' => 'nl', 'word' => 'BESTAAND', 'is_valid' => true]);

    $url = URL::signedRoute('dictionary.add-word', [
        'word' => 'BESTAAND',
        'language' => 'nl',
    ]);

    $response = $this->get($url);

    $response->assertOk();

    expect(Dictionary::where('language', 'nl')->where('word', 'BESTAAND')->count())->toBe(1);

    Queue::assertNotPushed(FetchWordDefinitionJob::class);
});

it('re-enables an invalidated word', function (): void {
    Queue::fake();

    Dictionary::create([
        'language' => 'nl',
        'word' => 'GA',
        'is_valid' => false,
        'requested_to_mark_as_invalid_at' => now(),
    ]);

    $url = URL::signedRoute('dictionary.add-word', [
        'word' => 'GA',
        'language' => 'nl',
    ]);

    $response = $this->get($url);

    $response->assertOk();
    $response->assertViewHas('action', 'added');

    $dictionary = Dictionary::where('language', 'nl')->where('word', 'GA')->first();

    expect($dictionary->is_valid)->toBeTrue();
    expect($dictionary->requested_to_mark_as_invalid_at)->toBeNull();
    expect(Dictionary::where('language', 'nl')->where('word', 'GA')->count())->toBe(1);

    Queue::assertPushed(FetchWordDefinitionJob::class, function (FetchWordDefinitionJob $job) {
        return $job->word === 'GA' && $job->language === 'nl';
    });
});

it('cannot add word without valid signature', function (): void {
    $response = $this->get('/dictionary/add-word?word=NIEUW&language=nl');

    $response->assertForbidden();

    expect(Dictionary::where('word', 'NIEUW')->exists())->toBeFalse();
});
