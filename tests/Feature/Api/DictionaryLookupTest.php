<?php

use App\Domain\Support\Data\WordDefinitionData;
use App\Domain\Support\Models\Dictionary;
use App\Domain\User\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    $definition = new WordDefinitionData(
        senses: [['definition' => 'A building to live in', 'pos' => 'noun']],
        etymology: 'From old Dutch',
    );

    Dictionary::create([
        'language' => 'nl',
        'word' => 'HUIS',
        'is_valid' => true,
        'definition' => $definition->toJson(),
        'times_played' => 5,
    ]);
});

it('can look up an existing word', function (): void {
    Sanctum::actingAs(User::factory()->create());

    $response = $this->getJson('/api/dictionary/lookup?word=huis&language=nl');

    $response->assertOk()
        ->assertJsonPath('found', true)
        ->assertJsonPath('data.word', 'HUIS')
        ->assertJsonPath('data.times_played', 5)
        ->assertJsonPath('data.definition.senses.0.definition', 'A building to live in');
});

it('returns found false for non-existent word', function (): void {
    Sanctum::actingAs(User::factory()->create());

    $response = $this->getJson('/api/dictionary/lookup?word=xyzabc&language=nl');

    $response->assertOk()
        ->assertJsonPath('found', false)
        ->assertJsonPath('word', 'XYZABC')
        ->assertJsonPath('language', 'nl');
});

it('normalizes word to uppercase', function (): void {
    Sanctum::actingAs(User::factory()->create());

    $response = $this->getJson('/api/dictionary/lookup?word=HUIS&language=nl');

    $response->assertOk()
        ->assertJsonPath('found', true)
        ->assertJsonPath('data.word', 'HUIS');
});

it('does not find invalid words', function (): void {
    Dictionary::where('word', 'HUIS')->update(['is_valid' => false]);

    Sanctum::actingAs(User::factory()->create());

    $response = $this->getJson('/api/dictionary/lookup?word=huis&language=nl');

    $response->assertOk()
        ->assertJsonPath('found', false);
});

it('requires authentication', function (): void {
    $response = $this->getJson('/api/dictionary/lookup?word=huis&language=nl');

    $response->assertUnauthorized();
});

it('validates required word', function (): void {
    Sanctum::actingAs(User::factory()->create());

    $response = $this->getJson('/api/dictionary/lookup?language=nl');

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['word']);
});

it('validates required language', function (): void {
    Sanctum::actingAs(User::factory()->create());

    $response = $this->getJson('/api/dictionary/lookup?word=huis');

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['language']);
});

it('validates language must be nl or en', function (): void {
    Sanctum::actingAs(User::factory()->create());

    $response = $this->getJson('/api/dictionary/lookup?word=huis&language=de');

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['language']);
});
