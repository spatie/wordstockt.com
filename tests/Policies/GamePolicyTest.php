<?php

use App\Domain\Game\Enums\GameStatus;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\GamePlayer;
use App\Domain\Game\Policies\GamePolicy;
use App\Domain\User\Enums\InvitationStatus;
use App\Domain\User\Models\GameInvitation;
use App\Domain\User\Models\User;

beforeEach(function (): void {
    $this->policy = new GamePolicy;
});

it('allows player to view their game', function (): void {
    $user = User::factory()->create();
    $game = createGameWithPlayers(player1: $user);

    expect($this->policy->view($user, $game))->toBeTrue();
});

it('denies non-player from viewing game', function (): void {
    $user = User::factory()->create();
    $game = createGameWithPlayers();

    expect($this->policy->view($user, $game))->toBeFalse();
});

it('allows invited user to view game', function (): void {
    $invitee = User::factory()->create();
    $inviter = User::factory()->create();

    $game = Game::factory()->create([
        'status' => GameStatus::Pending,
        'tile_bag' => createDefaultTileBag(),
    ]);
    GamePlayer::factory()->create([
        'game_id' => $game->id,
        'user_id' => $inviter->id,
        'rack_tiles' => createDefaultRack(),
        'turn_order' => 1,
    ]);

    GameInvitation::create([
        'game_id' => $game->id,
        'inviter_id' => $inviter->id,
        'invitee_id' => $invitee->id,
        'status' => InvitationStatus::Pending,
    ]);

    expect($this->policy->view($invitee, $game))->toBeTrue();
});

it('allows any authenticated user to create a game', function (): void {
    $user = User::factory()->create();

    expect($this->policy->create($user))->toBeTrue();
});

it('allows user to join pending game', function (): void {
    $creator = User::factory()->create();
    $joiner = User::factory()->create();

    $game = Game::factory()->create([
        'status' => GameStatus::Pending,
        'tile_bag' => createDefaultTileBag(),
    ]);
    GamePlayer::factory()->create([
        'game_id' => $game->id,
        'user_id' => $creator->id,
        'rack_tiles' => createDefaultRack(),
        'turn_order' => 1,
    ]);

    expect($this->policy->join($joiner, $game))->toBeTrue();
});

it('denies joining active game', function (): void {
    $user = User::factory()->create();
    $game = createGameWithPlayers(status: GameStatus::Active);

    expect($this->policy->join($user, $game))->toBeFalse();
});

it('denies joining finished game', function (): void {
    $user = User::factory()->create();
    $game = createGameWithPlayers(status: GameStatus::Finished);

    expect($this->policy->join($user, $game))->toBeFalse();
});

it('denies joining game user is already in', function (): void {
    $user = User::factory()->create();

    $game = Game::factory()->create([
        'status' => GameStatus::Pending,
        'tile_bag' => createDefaultTileBag(),
    ]);
    GamePlayer::factory()->create([
        'game_id' => $game->id,
        'user_id' => $user->id,
        'rack_tiles' => createDefaultRack(),
        'turn_order' => 1,
    ]);

    expect($this->policy->join($user, $game))->toBeFalse();
});

it('denies joining full game', function (): void {
    $user = User::factory()->create();
    $game = createGameWithPlayers(status: GameStatus::Pending);

    expect($this->policy->join($user, $game))->toBeFalse();
});

it('allows player to play in their game', function (): void {
    $user = User::factory()->create();
    $game = createGameWithPlayers(player1: $user);

    expect($this->policy->play($user, $game))->toBeTrue();
});

it('denies non-player from playing', function (): void {
    $user = User::factory()->create();
    $game = createGameWithPlayers();

    expect($this->policy->play($user, $game))->toBeFalse();
});

it('allows player to resign from their game', function (): void {
    $user = User::factory()->create();
    $game = createGameWithPlayers(player1: $user);

    expect($this->policy->resign($user, $game))->toBeTrue();
});

it('denies non-player from resigning', function (): void {
    $user = User::factory()->create();
    $game = createGameWithPlayers();

    expect($this->policy->resign($user, $game))->toBeFalse();
});
