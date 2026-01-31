<?php

use App\Domain\Game\Actions\PlayMoveAction;
use App\Domain\Support\Models\Dictionary;
use App\Domain\User\Models\User;
use Illuminate\Support\Carbon;

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
