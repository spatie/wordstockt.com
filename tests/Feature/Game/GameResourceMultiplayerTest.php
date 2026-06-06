<?php

use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\GamePlayer;
use App\Domain\User\Enums\InvitationStatus;
use App\Domain\User\Models\GameInvitation;
use App\Domain\User\Models\User;
use App\Http\Resources\GameListResource;
use App\Http\Resources\GameResource;
use Illuminate\Http\Request;

it('exposes max_players and per-player left state on the game resource', function (): void {
    $users = User::factory()->count(3)->create();
    $game = Game::factory()->active()->create(['max_players' => 3, 'current_turn_user_id' => $users[0]->id]);
    GamePlayer::factory()->for($game)->create(['user_id' => $users[0]->id, 'turn_order' => 1]);
    GamePlayer::factory()->for($game)->create(['user_id' => $users[1]->id, 'turn_order' => 2]);
    GamePlayer::factory()->for($game)->left('removed')->create(['user_id' => $users[2]->id, 'turn_order' => 3]);

    $request = Request::create('/');
    $request->setUserResolver(fn () => $users[0]);
    $array = (new GameResource($game->fresh(['gamePlayers.user', 'currentTurnUser'])))->toArray($request);

    expect($array['max_players'])->toBe(3)
        ->and($array['players'])->toHaveCount(3)
        ->and(collect($array['players'])->firstWhere('ulid', $users[2]->ulid)['has_left'])->toBeTrue();
});

it('exposes every pending invitation in creation order on the game resource', function (): void {
    $creator = User::factory()->create();
    $marvin = User::factory()->create(['username' => 'marvin']);
    $jessica = User::factory()->create(['username' => 'jessica']);

    $game = Game::factory()->pending()->create(['max_players' => 4]);
    GamePlayer::factory()->for($game)->create(['user_id' => $creator->id, 'turn_order' => 1]);

    GameInvitation::create(['game_id' => $game->id, 'inviter_id' => $creator->id, 'invitee_id' => $marvin->id, 'status' => InvitationStatus::Pending]);
    GameInvitation::create(['game_id' => $game->id, 'inviter_id' => $creator->id, 'invitee_id' => $jessica->id, 'status' => InvitationStatus::Pending]);

    $request = Request::create('/');
    $request->setUserResolver(fn () => $creator);
    $array = (new GameResource($game->fresh()))->toArray($request);

    expect($array['pending_invitations'])->toHaveCount(2)
        ->and($array['pending_invitations'][0]['invitee']['username'])->toBe('marvin')
        ->and($array['pending_invitations'][1]['invitee']['username'])->toBe('jessica');
});

it('returns all players (not a single opponent) on the list resource', function (): void {
    $users = User::factory()->count(3)->create();
    $game = Game::factory()->active()->create(['max_players' => 3, 'current_turn_user_id' => $users[0]->id]);
    $users->each(fn (User $u, int $i) => GamePlayer::factory()->for($game)->create(['user_id' => $u->id, 'turn_order' => $i + 1, 'score' => ($i + 1) * 10]));

    $request = Request::create('/');
    $request->setUserResolver(fn () => $users[0]);
    $array = (new GameListResource($game->fresh(['gamePlayers.user', 'players'])))->toArray($request);

    expect($array['players'])->toHaveCount(3)
        ->and($array['my_score'])->toBe(10)
        ->and($array['max_players'])->toBe(3);
});
