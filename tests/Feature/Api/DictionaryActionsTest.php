<?php

use App\Domain\Support\Models\Dictionary;
use Illuminate\Support\Facades\URL;

beforeEach(function (): void {
    Dictionary::create([
        'language' => 'nl',
        'word' => 'TEST',
        'is_valid' => true,
        'requested_to_mark_as_invalid_at' => now(),
    ]);
});

it('can invalidate a word via signed url', function (): void {
    $dictionary = Dictionary::where('word', 'TEST')->first();

    $url = URL::signedRoute('dictionary.invalidate', $dictionary);

    $response = $this->get($url);

    $response->assertOk();
    $response->assertViewIs('dictionary.action-confirmed');
    $response->assertViewHas('action', 'invalidated');
    $response->assertViewHas('word', 'TEST');

    expect($dictionary->fresh()->is_valid)->toBeFalse();
    expect($dictionary->fresh()->requested_to_mark_as_invalid_at)->toBeNull();
});

it('cannot invalidate without valid signature', function (): void {
    $dictionary = Dictionary::where('word', 'TEST')->first();

    $response = $this->get("/dictionary/{$dictionary->id}/invalidate");

    $response->assertForbidden();

    expect($dictionary->fresh()->is_valid)->toBeTrue();
});

it('can dismiss a report via signed url', function (): void {
    $dictionary = Dictionary::where('word', 'TEST')->first();

    $url = URL::signedRoute('dictionary.dismiss', $dictionary);

    $response = $this->get($url);

    $response->assertOk();
    $response->assertViewIs('dictionary.action-confirmed');
    $response->assertViewHas('action', 'dismissed');
    $response->assertViewHas('word', 'TEST');

    expect($dictionary->fresh()->is_valid)->toBeTrue();
    expect($dictionary->fresh()->requested_to_mark_as_invalid_at)->toBeNull();
});

it('cannot dismiss without valid signature', function (): void {
    $dictionary = Dictionary::where('word', 'TEST')->first();

    $response = $this->get("/dictionary/{$dictionary->id}/dismiss");

    $response->assertForbidden();

    expect($dictionary->fresh()->requested_to_mark_as_invalid_at)->not->toBeNull();
});
