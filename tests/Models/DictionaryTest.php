<?php

use App\Domain\Game\Actions\PlayMoveAction;
use App\Domain\Support\Models\Dictionary;
use App\Domain\User\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

beforeEach(function (): void {
    Dictionary::create(['language' => 'nl', 'word' => 'TEST']);
    Dictionary::create(['language' => 'nl', 'word' => 'HUIS']);
    Dictionary::create(['language' => 'en', 'word' => 'TEST']);
    Dictionary::create(['language' => 'en', 'word' => 'CAB']);
});

it('records times played for a word', function (): void {
    Dictionary::recordPlay('TEST', 'nl');

    $dictionary = Dictionary::where('language', 'nl')->where('word', 'TEST')->first();

    expect($dictionary->times_played)->toBe(1);
});

it('increments times played on multiple plays', function (): void {
    Dictionary::recordPlay('TEST', 'nl');
    Dictionary::recordPlay('TEST', 'nl');
    Dictionary::recordPlay('TEST', 'nl');

    $dictionary = Dictionary::where('language', 'nl')->where('word', 'TEST')->first();

    expect($dictionary->times_played)->toBe(3);
});

it('sets first_played_at on first play', function (): void {
    Carbon::setTestNow('2026-01-08 12:00:00');

    Dictionary::recordPlay('TEST', 'nl');

    $dictionary = Dictionary::where('language', 'nl')->where('word', 'TEST')->first();

    expect($dictionary->first_played_at->toDateTimeString())->toBe('2026-01-08 12:00:00');
});

it('does not update first_played_at on subsequent plays', function (): void {
    Carbon::setTestNow('2026-01-08 12:00:00');
    Dictionary::recordPlay('TEST', 'nl');

    Carbon::setTestNow('2026-01-09 15:00:00');
    Dictionary::recordPlay('TEST', 'nl');

    $dictionary = Dictionary::where('language', 'nl')->where('word', 'TEST')->first();

    expect($dictionary->first_played_at->toDateTimeString())->toBe('2026-01-08 12:00:00');
});

it('updates last_played_at on every play', function (): void {
    Carbon::setTestNow('2026-01-08 12:00:00');
    Dictionary::recordPlay('TEST', 'nl');

    Carbon::setTestNow('2026-01-09 15:00:00');
    Dictionary::recordPlay('TEST', 'nl');

    $dictionary = Dictionary::where('language', 'nl')->where('word', 'TEST')->first();

    expect($dictionary->last_played_at->toDateTimeString())->toBe('2026-01-09 15:00:00');
});

it('handles lowercase words', function (): void {
    Dictionary::recordPlay('test', 'nl');

    $dictionary = Dictionary::where('language', 'nl')->where('word', 'TEST')->first();

    expect($dictionary->times_played)->toBe(1);
});

it('handles words with whitespace', function (): void {
    Dictionary::recordPlay('  TEST  ', 'nl');

    $dictionary = Dictionary::where('language', 'nl')->where('word', 'TEST')->first();

    expect($dictionary->times_played)->toBe(1);
});

it('does not fail for non-existent words', function (): void {
    Dictionary::recordPlay('NONEXISTENT', 'nl');

    expect(true)->toBeTrue();
});

it('tracks stats per language', function (): void {
    Dictionary::recordPlay('TEST', 'nl');
    Dictionary::recordPlay('TEST', 'nl');
    Dictionary::recordPlay('TEST', 'en');

    $nlDictionary = Dictionary::where('language', 'nl')->where('word', 'TEST')->first();
    $enDictionary = Dictionary::where('language', 'en')->where('word', 'TEST')->first();

    expect($nlDictionary->times_played)->toBe(2);
    expect($enDictionary->times_played)->toBe(1);
});

it('records multiple words at once', function (): void {
    Dictionary::recordPlays(['TEST', 'HUIS'], 'nl');

    $test = Dictionary::where('language', 'nl')->where('word', 'TEST')->first();
    $huis = Dictionary::where('language', 'nl')->where('word', 'HUIS')->first();

    expect($test->times_played)->toBe(1);
    expect($huis->times_played)->toBe(1);
});

it('records the same word multiple times in a single call', function (): void {
    Dictionary::recordPlays(['TEST', 'TEST'], 'nl');

    $dictionary = Dictionary::where('language', 'nl')->where('word', 'TEST')->first();

    expect($dictionary->times_played)->toBe(2);
});

it('updates dictionary stats when a move is played', function (): void {
    $user = User::factory()->create();
    $game = createGameWithPlayers(player1: $user, language: 'en');

    $game->getGamePlayer($user)->update([
        'rack_tiles' => [
            ['letter' => 'C', 'points' => 3, 'is_blank' => false],
            ['letter' => 'A', 'points' => 1, 'is_blank' => false],
            ['letter' => 'B', 'points' => 3, 'is_blank' => false],
            ['letter' => 'D', 'points' => 2, 'is_blank' => false],
            ['letter' => 'E', 'points' => 1, 'is_blank' => false],
            ['letter' => 'F', 'points' => 4, 'is_blank' => false],
            ['letter' => 'G', 'points' => 2, 'is_blank' => false],
        ],
    ]);

    $tiles = [
        ['letter' => 'C', 'points' => 3, 'x' => 7, 'y' => 7, 'is_blank' => false],
        ['letter' => 'A', 'points' => 1, 'x' => 8, 'y' => 7, 'is_blank' => false],
        ['letter' => 'B', 'points' => 3, 'x' => 9, 'y' => 7, 'is_blank' => false],
    ];

    app(PlayMoveAction::class)->execute($game, $user, $tiles);

    $dictionary = Dictionary::where('language', 'en')->where('word', 'CAB')->first();

    expect($dictionary->times_played)->toBe(1);
    expect($dictionary->first_played_at)->not->toBeNull();
    expect($dictionary->last_played_at)->not->toBeNull();
});

it('can invalidate a word', function (): void {
    $dictionary = Dictionary::where('language', 'nl')->where('word', 'TEST')->first();

    expect($dictionary->is_valid)->toBeTrue();

    $dictionary->invalidate();

    expect($dictionary->fresh()->is_valid)->toBeFalse();
});

it('clears cache when invalidating a word', function (): void {
    Dictionary::isValidWord('TEST', 'nl');

    expect(Cache::has('dictionary:nl:TEST'))->toBeTrue();

    $dictionary = Dictionary::where('language', 'nl')->where('word', 'TEST')->first();
    $dictionary->invalidate();

    expect(Cache::has('dictionary:nl:TEST'))->toBeFalse();
});

it('clears requested_to_mark_as_invalid_at when invalidating', function (): void {
    $dictionary = Dictionary::where('language', 'nl')->where('word', 'TEST')->first();
    $dictionary->requestInvalidation();

    expect($dictionary->fresh()->requested_to_mark_as_invalid_at)->not->toBeNull();

    $dictionary->invalidate();

    expect($dictionary->fresh()->requested_to_mark_as_invalid_at)->toBeNull();
});

it('can request invalidation', function (): void {
    Carbon::setTestNow('2026-01-15 10:00:00');

    $dictionary = Dictionary::where('language', 'nl')->where('word', 'TEST')->first();

    expect($dictionary->requested_to_mark_as_invalid_at)->toBeNull();

    $dictionary->requestInvalidation();

    expect($dictionary->fresh()->requested_to_mark_as_invalid_at->toDateTimeString())->toBe('2026-01-15 10:00:00');
});

it('does not update requested_to_mark_as_invalid_at if already requested', function (): void {
    Carbon::setTestNow('2026-01-15 10:00:00');

    $dictionary = Dictionary::where('language', 'nl')->where('word', 'TEST')->first();
    $dictionary->requestInvalidation();

    Carbon::setTestNow('2026-01-16 12:00:00');
    $dictionary->requestInvalidation();

    expect($dictionary->fresh()->requested_to_mark_as_invalid_at->toDateTimeString())->toBe('2026-01-15 10:00:00');
});

it('does not consider invalid words as valid', function (): void {
    Cache::flush();

    $dictionary = Dictionary::where('language', 'nl')->where('word', 'TEST')->first();
    $dictionary->update(['is_valid' => false]);

    expect(Dictionary::isValidWord('TEST', 'nl'))->toBeFalse();
});

it('considers valid words as valid', function (): void {
    Cache::flush();

    expect(Dictionary::isValidWord('TEST', 'nl'))->toBeTrue();
});

it('can dismiss a report', function (): void {
    $dictionary = Dictionary::where('language', 'nl')->where('word', 'TEST')->first();
    $dictionary->requestInvalidation();

    expect($dictionary->fresh()->requested_to_mark_as_invalid_at)->not->toBeNull();

    $dictionary->dismissReport();

    expect($dictionary->fresh()->requested_to_mark_as_invalid_at)->toBeNull();
    expect($dictionary->fresh()->is_valid)->toBeTrue();
});
