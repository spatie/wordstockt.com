<?php

use App\Domain\Game\Enums\GameStatus;
use App\Domain\Game\Enums\MoveType;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\GamePlayer;
use App\Domain\Game\Models\Move;
use App\Domain\User\Enums\InvitationStatus;
use App\Domain\User\Models\GameInvitation;
use App\Domain\User\Models\User;

it('returns user games', function (): void {
    $user = User::factory()->create();
    $game = createGameWithPlayers(player1: $user);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/games');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => ['ulid', 'language', 'status', 'opponent', 'my_score', 'is_my_turn'],
            ],
        ]);

    expect($response->json('data'))->toHaveCount(1);
});

it('does not return games user is not part of', function (): void {
    $user = User::factory()->create();
    createGameWithPlayers(); // Game without this user

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/games');

    $response->assertOk();
    expect($response->json('data'))->toBeEmpty();
});

it('returns multiple games', function (): void {
    $user = User::factory()->create();
    createGameWithPlayers(player1: $user);
    createGameWithPlayers(player1: $user);
    createGameWithPlayers(player1: $user);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/games');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(3);
});

it('includes opponent information', function (): void {
    $user = User::factory()->create();
    $opponent = User::factory()->create(['username' => 'opponent']);
    createGameWithPlayers(player1: $user, player2: $opponent);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/games');

    $response->assertOk()
        ->assertJsonPath('data.0.opponent.username', 'opponent');
});

it('shows correct is_my_turn flag', function (): void {
    $user = User::factory()->create();
    $opponent = User::factory()->create();
    $game = createGameWithPlayers(player1: $user, player2: $opponent);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/games');

    $response->assertOk()
        ->assertJsonPath('data.0.is_my_turn', true);
});

it('returns 401 for unauthenticated request', function (): void {
    $response = $this->getJson('/api/games');

    $response->assertStatus(401);
});

it('returns "Game Started!" when no moves exist', function (): void {
    $user = User::factory()->create();
    createGameWithPlayers(player1: $user);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/games');

    $response->assertOk()
        ->assertJsonPath('data.0.last_move_description', 'Game Started!');
});

it('shows word play from opponent with highest-scoring word', function (): void {
    $user = User::factory()->create();
    $opponent = User::factory()->create(['username' => 'Sarah']);
    $game = createGameWithPlayers(player1: $user, player2: $opponent);

    Move::create([
        'game_id' => $game->id,
        'user_id' => $opponent->id,
        'type' => MoveType::Play,
        'tiles' => [['letter' => 'M', 'x' => 7, 'y' => 7]],
        'words' => [
            ['word' => 'am', 'score' => 4],
            ['word' => 'majesty', 'score' => 15],
        ],
        'score' => 19,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/games');

    $response->assertOk()
        ->assertJsonPath('data.0.last_move_description', "Sarah played 'MAJESTY' for 19 points");
});

it('shows word play from user as "You"', function (): void {
    $user = User::factory()->create();
    $opponent = User::factory()->create();
    $game = createGameWithPlayers(player1: $user, player2: $opponent);

    Move::create([
        'game_id' => $game->id,
        'user_id' => $user->id,
        'type' => MoveType::Play,
        'tiles' => [['letter' => 'H', 'x' => 7, 'y' => 7]],
        'words' => [['word' => 'hello', 'score' => 8]],
        'score' => 8,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/games');

    $response->assertOk()
        ->assertJsonPath('data.0.last_move_description', "You played 'HELLO' for 8 points");
});

it('shows pass move from opponent', function (): void {
    $user = User::factory()->create();
    $opponent = User::factory()->create(['username' => 'John']);
    $game = createGameWithPlayers(player1: $user, player2: $opponent);

    Move::create([
        'game_id' => $game->id,
        'user_id' => $opponent->id,
        'type' => MoveType::Pass,
        'tiles' => [],
        'words' => [],
        'score' => 0,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/games');

    $response->assertOk()
        ->assertJsonPath('data.0.last_move_description', 'John passed');
});

it('shows pass move from user as "You"', function (): void {
    $user = User::factory()->create();
    $opponent = User::factory()->create();
    $game = createGameWithPlayers(player1: $user, player2: $opponent);

    Move::create([
        'game_id' => $game->id,
        'user_id' => $user->id,
        'type' => MoveType::Pass,
        'tiles' => [],
        'words' => [],
        'score' => 0,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/games');

    $response->assertOk()
        ->assertJsonPath('data.0.last_move_description', 'You passed');
});

it('shows swap move from opponent', function (): void {
    $user = User::factory()->create();
    $opponent = User::factory()->create(['username' => 'Alice']);
    $game = createGameWithPlayers(player1: $user, player2: $opponent);

    Move::create([
        'game_id' => $game->id,
        'user_id' => $opponent->id,
        'type' => MoveType::Swap,
        'tiles' => [['letter' => 'A', 'points' => 1]],
        'words' => [],
        'score' => 0,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/games');

    $response->assertOk()
        ->assertJsonPath('data.0.last_move_description', 'Alice swapped tiles');
});

it('shows swap move from user as "You"', function (): void {
    $user = User::factory()->create();
    $opponent = User::factory()->create();
    $game = createGameWithPlayers(player1: $user, player2: $opponent);

    Move::create([
        'game_id' => $game->id,
        'user_id' => $user->id,
        'type' => MoveType::Swap,
        'tiles' => [['letter' => 'A', 'points' => 1]],
        'words' => [],
        'score' => 0,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/games');

    $response->assertOk()
        ->assertJsonPath('data.0.last_move_description', 'You swapped tiles');
});

it('shows resign move from opponent', function (): void {
    $user = User::factory()->create();
    $opponent = User::factory()->create(['username' => 'Bob']);
    $game = createGameWithPlayers(player1: $user, player2: $opponent);

    Move::create([
        'game_id' => $game->id,
        'user_id' => $opponent->id,
        'type' => MoveType::Resign,
        'tiles' => [],
        'words' => [],
        'score' => 0,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/games');

    $response->assertOk()
        ->assertJsonPath('data.0.last_move_description', 'Bob resigned');
});

it('shows resign move from user as "You"', function (): void {
    $user = User::factory()->create();
    $opponent = User::factory()->create();
    $game = createGameWithPlayers(player1: $user, player2: $opponent);

    Move::create([
        'game_id' => $game->id,
        'user_id' => $user->id,
        'type' => MoveType::Resign,
        'tiles' => [],
        'words' => [],
        'score' => 0,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/games');

    $response->assertOk()
        ->assertJsonPath('data.0.last_move_description', 'You resigned');
});

it('shows latest move when multiple moves exist', function (): void {
    $user = User::factory()->create();
    $opponent = User::factory()->create(['username' => 'Eve']);
    $game = createGameWithPlayers(player1: $user, player2: $opponent);

    // First move (older)
    Move::create([
        'game_id' => $game->id,
        'user_id' => $user->id,
        'type' => MoveType::Play,
        'tiles' => [['letter' => 'H', 'x' => 7, 'y' => 7]],
        'words' => [['word' => 'hello', 'score' => 8]],
        'score' => 8,
        'created_at' => now()->subMinutes(5),
    ]);

    // Second move (newer - should be shown)
    Move::create([
        'game_id' => $game->id,
        'user_id' => $opponent->id,
        'type' => MoveType::Play,
        'tiles' => [['letter' => 'W', 'x' => 8, 'y' => 7]],
        'words' => [['word' => 'world', 'score' => 12]],
        'score' => 12,
        'created_at' => now(),
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/games');

    $response->assertOk()
        ->assertJsonPath('data.0.last_move_description', "Eve played 'WORLD' for 12 points");
});

it('returns pending invitation with game in list', function (): void {
    $creator = User::factory()->create();
    $invitee = User::factory()->create();

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

    GameInvitation::create([
        'game_id' => $game->id,
        'inviter_id' => $creator->id,
        'invitee_id' => $invitee->id,
        'status' => InvitationStatus::Pending,
    ]);

    $response = $this->actingAs($creator, 'sanctum')
        ->getJson('/api/games');

    $response->assertOk()
        ->assertJsonPath('data.0.pending_invitation.invitee.ulid', $invitee->ulid)
        ->assertJsonPath('data.0.pending_invitation.invitee.username', $invitee->username);
});

it('returns null pending invitation when no invitation exists in list', function (): void {
    $creator = User::factory()->create();

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

    $response = $this->actingAs($creator, 'sanctum')
        ->getJson('/api/games');

    $response->assertOk()
        ->assertJsonPath('data.0.pending_invitation', null);
});
