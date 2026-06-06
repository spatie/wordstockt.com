<?php

use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\GamePlayer;
use App\Domain\Game\Notifications\TurnReminderNotification;
use App\Domain\User\Models\User;

it('warns about removal when the player has already passed once in a multiplayer game', function (): void {
    $users = User::factory()->count(3)->create();
    $game = Game::factory()->active()->create(['max_players' => 3, 'current_turn_user_id' => $users[0]->id]);
    GamePlayer::factory()->for($game)->create(['user_id' => $users[0]->id, 'turn_order' => 1, 'consecutive_passes' => 1]);
    GamePlayer::factory()->for($game)->create(['user_id' => $users[1]->id, 'turn_order' => 2]);
    GamePlayer::factory()->for($game)->create(['user_id' => $users[2]->id, 'turn_order' => 3]);

    $message = (new TurnReminderNotification($game->fresh(), 1, $users[1]))->toExpo($users[0]);

    expect($message->toArray()['body'])->toContain('removed');
});

it('does not warn about removal in a two-player game', function (): void {
    $users = User::factory()->count(2)->create();
    $game = Game::factory()->active()->create(['max_players' => 2, 'current_turn_user_id' => $users[0]->id]);
    GamePlayer::factory()->for($game)->create(['user_id' => $users[0]->id, 'turn_order' => 1, 'consecutive_passes' => 1]);
    GamePlayer::factory()->for($game)->create(['user_id' => $users[1]->id, 'turn_order' => 2]);

    $message = (new TurnReminderNotification($game->fresh(), 1, $users[1]))->toExpo($users[0]);

    expect($message->toArray()['body'])->not->toContain('removed');
});
