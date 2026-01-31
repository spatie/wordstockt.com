<?php

use App\Domain\Game\Actions\CreateGameAction;
use App\Domain\Game\Actions\JoinGameAction;
use App\Domain\Game\Enums\GameStatus;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\GamePlayer;
use App\Domain\User\Models\User;

beforeEach(function (): void {
    $this->createGameAction = new CreateGameAction;
    $this->joinGameAction = new JoinGameAction;
});

it('creates pending game with invitation when creating game with opponent username', function (): void {
    $creator = User::factory()->create();
    $opponent = User::factory()->create(['username' => 'opponent']);

    $game = $this->createGameAction->execute(
        creator: $creator,
        language: 'en',
        opponentUsername: $opponent->username,
    );

    // Game should be pending with an invitation for the opponent
    expect($game->status)->toBe(GameStatus::Pending)
        ->and($game->current_turn_user_id)->toBeNull()
        ->and($game->pendingInvitation)->not->toBeNull()
        ->and($game->pendingInvitation->invitee_id)->toBe($opponent->id);
});

it('randomly selects first player when joining pending game', function (): void {
    $creatorWentFirst = false;
    $joinerWentFirst = false;

    // Run multiple times to verify randomness
    for ($i = 0; $i < 20; $i++) {
        $creator = User::factory()->create();
        $joiner = User::factory()->create();

        $game = Game::factory()->create([
            'status' => GameStatus::Pending,
            'language' => 'en',
            'tile_bag' => createDefaultTileBag(),
        ]);

        GamePlayer::factory()->create([
            'game_id' => $game->id,
            'user_id' => $creator->id,
            'rack_tiles' => createDefaultRack(),
            'score' => 0,
            'turn_order' => 1,
        ]);

        $game = $this->joinGameAction->execute($game, $joiner);

        if ($game->current_turn_user_id === $creator->id) {
            $creatorWentFirst = true;
        }

        if ($game->current_turn_user_id === $joiner->id) {
            $joinerWentFirst = true;
        }

        // Break early if we've seen both outcomes
        if ($creatorWentFirst && $joinerWentFirst) {
            break;
        }
    }

    expect($creatorWentFirst)->toBeTrue('Creator should go first at least once')
        ->and($joinerWentFirst)->toBeTrue('Joiner should go first at least once');
});

it('game is active after opponent joins', function (): void {
    $creator = User::factory()->create();
    $joiner = User::factory()->create();

    $game = Game::factory()->create([
        'status' => GameStatus::Pending,
        'language' => 'en',
        'tile_bag' => createDefaultTileBag(),
    ]);

    GamePlayer::factory()->create([
        'game_id' => $game->id,
        'user_id' => $creator->id,
        'rack_tiles' => createDefaultRack(),
        'score' => 0,
        'turn_order' => 1,
    ]);

    $game = $this->joinGameAction->execute($game, $joiner);

    expect($game->status)->toBe(GameStatus::Active)
        ->and($game->current_turn_user_id)->toBeIn([$creator->id, $joiner->id]);
});
