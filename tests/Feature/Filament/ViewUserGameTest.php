<?php

use App\Domain\Game\Enums\GameStatus;
use App\Domain\User\Models\User;
use App\Filament\Resources\Users\UserResource;

it('shows a game from the user admin page', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $opponent = User::factory()->create(['username' => 'opponent']);
    $game = createGameWithPlayers(player1: $admin, player2: $opponent, status: GameStatus::Active);

    $this->actingAs($admin)
        ->get(UserResource::getUrl('games.view', ['record' => $admin, 'game' => $game]))
        ->assertOk()
        ->assertSee('Game vs opponent');
});

it('does not show a game the user did not play', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $game = createGameWithPlayers(player1: User::factory()->create(), player2: User::factory()->create(), status: GameStatus::Active);

    $this->actingAs($admin)
        ->get(UserResource::getUrl('games.view', ['record' => $admin, 'game' => $game]))
        ->assertNotFound();
});
