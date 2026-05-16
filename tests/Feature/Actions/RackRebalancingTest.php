<?php

use App\Domain\Game\Actions\PlayMoveAction;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\GamePlayer;
use App\Domain\Support\Models\Dictionary;
use App\Domain\User\Models\User;

it('rebalances a refilled rack so it is not left all vowels', function (): void {
    mt_srand(12345);

    Dictionary::create(['word' => 'CAT', 'language' => 'en']);

    $player = User::factory()->create();
    $opponent = User::factory()->create();

    $game = Game::factory()->create([
        'language' => 'en',
        'current_turn_user_id' => $player->id,
        'tile_bag' => [
            ['letter' => 'O', 'points' => 1],
            ['letter' => 'B', 'points' => 3],
            ['letter' => 'D', 'points' => 2],
            ['letter' => 'F', 'points' => 4],
            ['letter' => 'G', 'points' => 3],
            ['letter' => 'H', 'points' => 4],
            ['letter' => 'K', 'points' => 5],
            ['letter' => 'L', 'points' => 1],
        ],
        'board_state' => createBoardWithTiles([
            ['letter' => 'A', 'x' => 7, 'y' => 7, 'points' => 1],
            ['letter' => 'T', 'x' => 8, 'y' => 7, 'points' => 1],
        ]),
    ]);

    $gamePlayer = GamePlayer::factory()->create([
        'game_id' => $game->id,
        'user_id' => $player->id,
        'turn_order' => 1,
        'score' => 0,
        'rack_tiles' => [
            ['letter' => 'C', 'points' => 3],
            ['letter' => 'A', 'points' => 1],
            ['letter' => 'E', 'points' => 1],
            ['letter' => 'I', 'points' => 1],
            ['letter' => 'O', 'points' => 1],
            ['letter' => 'U', 'points' => 4],
        ],
    ]);

    GamePlayer::factory()->create([
        'game_id' => $game->id,
        'user_id' => $opponent->id,
        'turn_order' => 2,
        'score' => 0,
        'rack_tiles' => [['letter' => 'X', 'points' => 8]],
    ]);

    $game = $game->fresh(['players', 'gamePlayers']);

    app(PlayMoveAction::class)->execute($game, $player, [
        ['letter' => 'C', 'x' => 6, 'y' => 7, 'points' => 3, 'is_blank' => false],
    ]);

    $gamePlayer->refresh();

    $rackLetters = collect($gamePlayer->rack_tiles)->pluck('letter');
    $hasConsonant = $rackLetters->contains(
        fn (string $letter): bool => ! in_array($letter, ['A', 'E', 'I', 'O', 'U'], true)
    );

    expect($hasConsonant)->toBeTrue();
});
