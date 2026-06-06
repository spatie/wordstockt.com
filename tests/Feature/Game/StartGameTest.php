<?php

use App\Domain\Game\Actions\StartGameAction;
use App\Domain\Game\Enums\GameStatus;
use App\Domain\Game\Exceptions\GameException;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\GamePlayer;
use App\Domain\User\Enums\InvitationStatus;
use App\Domain\User\Models\GameInvitation;
use App\Domain\User\Models\User;

function pendingGameWith(int $maxPlayers, int $joined): Game
{
    $game = Game::factory()->pending()->create(['max_players' => $maxPlayers]);
    User::factory()->count($joined)->create()->each(
        fn (User $u, int $i) => GamePlayer::factory()->for($game)->create(['user_id' => $u->id, 'turn_order' => $i + 1])
    );

    return $game->fresh();
}

it('starts a partially filled game and cancels pending invitations', function (): void {
    $game = pendingGameWith(maxPlayers: 4, joined: 2);
    $invitee = User::factory()->create();
    GameInvitation::create([
        'game_id' => $game->id,
        'inviter_id' => $game->players()->first()->id,
        'invitee_id' => $invitee->id,
        'status' => InvitationStatus::Pending,
    ]);

    app(StartGameAction::class)->execute($game, $game->players()->first());
    $game->refresh();

    expect($game->status)->toBe(GameStatus::Active)
        ->and($game->pendingInvitations()->count())->toBe(0);
});

it('refuses to start with fewer than two players', function (): void {
    $game = pendingGameWith(maxPlayers: 3, joined: 1);

    app(StartGameAction::class)->execute($game, $game->players()->first());
})->throws(GameException::class);
