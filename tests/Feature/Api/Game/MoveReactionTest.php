<?php

use App\Domain\Game\Events\MoveReactionChanged;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\GamePlayer;
use App\Domain\Game\Models\Move;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    $this->player = User::factory()->create();
    $this->opponent = User::factory()->create();
    $this->game = Game::factory()->active()->create();

    GamePlayer::factory()->create(['game_id' => $this->game->id, 'user_id' => $this->player->id]);
    GamePlayer::factory()->create(['game_id' => $this->game->id, 'user_id' => $this->opponent->id]);

    $this->move = Move::factory()->create([
        'game_id' => $this->game->id,
        'user_id' => $this->opponent->id,
    ]);
});

it('stores one reaction per player and includes it in move history', function (): void {
    Event::fake([MoveReactionChanged::class]);
    Sanctum::actingAs($this->player);

    $url = "/api/games/{$this->game->ulid}/moves/{$this->move->ulid}/reaction";

    $this->putJson($url, ['reaction' => 'clap'])->assertOk();
    $this->putJson($url, ['reaction' => 'wow'])->assertOk();

    $this->assertDatabaseCount('move_reactions', 1);
    $this->assertDatabaseHas('move_reactions', [
        'move_id' => $this->move->id,
        'user_id' => $this->player->id,
        'reaction' => 'wow',
    ]);

    $this->getJson("/api/games/{$this->game->ulid}/moves")
        ->assertOk()
        ->assertJsonPath('data.0.reactions.0.user_ulid', $this->player->ulid)
        ->assertJsonPath('data.0.reactions.0.reaction', 'wow');

    Event::assertDispatched(MoveReactionChanged::class, 2);
});

it('removes a reaction and broadcasts the removal', function (): void {
    Event::fake([MoveReactionChanged::class]);
    Sanctum::actingAs($this->player);

    $url = "/api/games/{$this->game->ulid}/moves/{$this->move->ulid}/reaction";

    $this->putJson($url, ['reaction' => 'laugh'])->assertOk();
    $this->deleteJson($url)->assertOk()->assertJsonPath('reaction', null);

    $this->assertDatabaseCount('move_reactions', 0);
    Event::assertDispatched(MoveReactionChanged::class, fn (MoveReactionChanged $event): bool => $event->reaction === null);
});

it('rejects invalid reactions and reactions to your own move', function (): void {
    Sanctum::actingAs($this->player);

    $url = "/api/games/{$this->game->ulid}/moves/{$this->move->ulid}/reaction";

    $this->putJson($url, ['reaction' => 'arbitrary'])->assertUnprocessable();

    $this->move->update(['user_id' => $this->player->id]);

    $this->putJson($url, ['reaction' => 'clap'])->assertForbidden();
    $this->deleteJson($url)->assertForbidden();
});

it('rejects outsiders and moves from another game', function (): void {
    $otherGame = Game::factory()->active()->create();
    $url = "/api/games/{$otherGame->ulid}/moves/{$this->move->ulid}/reaction";

    Sanctum::actingAs($this->player);
    $this->putJson($url, ['reaction' => 'clap'])->assertNotFound();
    $this->deleteJson($url)->assertNotFound();

    Sanctum::actingAs(User::factory()->create());
    $url = "/api/games/{$this->game->ulid}/moves/{$this->move->ulid}/reaction";
    $this->putJson($url, ['reaction' => 'clap'])->assertForbidden();
});
