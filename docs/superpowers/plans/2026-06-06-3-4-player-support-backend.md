# 3-4 Player Support (Backend) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Generalize the Laravel backend from exactly-two-player games to 2, 3, or 4 player games, including seat counts, manual start, player removal on double-pass, all-players broadcasting, pairwise ELO, and API serialization for N players.

**Architecture:** The pivot (`game_players`) already carries `turn_order`, so turn order and winner-by-score generalize naturally. We add a seat count (`games.max_players`) and a per-player "left" state plus a per-player consecutive-pass counter (`game_players`). Turn rotation skips left players; two consecutive passes removes a player in 3-4 player games; the last active player wins. Two-player behavior is preserved byte-for-byte by guarding the new mechanics on `gamePlayers()->count() > 2`. Stats gain a multiplayer branch using pairwise ELO averaged over N-1 opponents.

**Tech Stack:** PHP 8.5, Laravel 12, Pest 4, SQLite (dev/test). Domain-driven layout under `app/Domain/Game`. Actions have an `execute()` method. No `DB::transaction()`, no `else`, no `final`, no `private const`.

**Spec:** `docs/superpowers/specs/2026-06-06-3-4-player-support-design.md`

**Conventions reminder:** run `vendor/bin/pint` before committing. Run tests with `php artisan test --compact --filter=...`. Create files via `php artisan make:*` where applicable. Commit after every task.

---

## File Structure

**Migrations (create):**
- `database/migrations/2026_06_06_000001_add_max_players_to_games_table.php`
- `database/migrations/2026_06_06_000002_add_multiplayer_columns_to_game_players_table.php`

**Models (modify):**
- `app/Domain/Game/Models/Game.php` — seat helpers, `max_players` cast, `pendingInvitations`, active/other-player helpers, `isMultiplayer()`.
- `app/Domain/Game/Models/GamePlayer.php` — casts for new columns, `hasLeft()`, `scopeActive()`.

**Actions (create):**
- `app/Domain/Game/Actions/ActivateGameAction.php`
- `app/Domain/Game/Actions/StartGameAction.php`
- `app/Domain/Game/Actions/RemovePlayerAction.php`

**Actions (modify):**
- `CreateGameAction`, `JoinGameAction`, `InvitePlayerAction`, `SwitchTurnAction`, `PassAction`, `AutoPassAction`, `PlayMoveAction`, `SwapTilesAction`, `ResignAction`, `EndGameAction`, `Stats/UpdateGameEndStatsAction`.

**Support (create):**
- `app/Domain/User/Support/EloCalculator/MultiplayerEloCalculator.php`

**Rules (modify):**
- `app/Domain/Game/Support/Rules/EndGame/ConsecutivePassRule.php` — guard to 2-player only.

**Events (modify):**
- `app/Domain/Game/Events/MovePlayed.php` — broadcast to all other players.

**HTTP (create/modify):**
- `app/Http/Controllers/Api/Game/StartController.php` (create) + route.
- `app/Http/Requests/Game/StoreGameRequest.php` — `max_players`.
- `app/Http/Resources/GameResource.php`, `GameListResource.php`, `PendingGameResource.php` — N-player shape.

**Tests (modify):**
- `tests/Helpers/TestHelpers.php` — add `createGameWithNPlayers()`.

---

## Task 1: Schema + factories for seat count and per-player multiplayer state

**Files:**
- Create: `database/migrations/2026_06_06_000001_add_max_players_to_games_table.php`
- Create: `database/migrations/2026_06_06_000002_add_multiplayer_columns_to_game_players_table.php`
- Modify: `database/factories/GameFactory.php`, `database/factories/GamePlayerFactory.php`

- [ ] **Step 1: Write the games migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('games', function (Blueprint $table): void {
            $table->unsignedTinyInteger('max_players')->default(2)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('games', function (Blueprint $table): void {
            $table->dropColumn('max_players');
        });
    }
};
```

- [ ] **Step 2: Write the game_players migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('game_players', function (Blueprint $table): void {
            $table->unsignedTinyInteger('consecutive_passes')->default(0)->after('turn_order');
            $table->timestamp('left_at')->nullable()->after('consecutive_passes');
            $table->string('left_reason')->nullable()->after('left_at');
        });
    }

    public function down(): void
    {
        Schema::table('game_players', function (Blueprint $table): void {
            $table->dropColumn(['consecutive_passes', 'left_at', 'left_reason']);
        });
    }
};
```

- [ ] **Step 3: Update GameFactory definition to include max_players**

In `database/factories/GameFactory.php`, add `'max_players' => 2,` to the array returned by `definition()`.

- [ ] **Step 4: Update GamePlayerFactory definition**

In `database/factories/GamePlayerFactory.php`, add to `definition()`:
```php
'consecutive_passes' => 0,
'left_at' => null,
'left_reason' => null,
```
Add a state method:
```php
public function left(string $reason = 'removed'): static
{
    return $this->state(fn (): array => ['left_at' => now(), 'left_reason' => $reason]);
}
```

- [ ] **Step 5: Run the migrations against the test database**

Run: `php artisan migrate --no-interaction`
Expected: both migrations run with no error. (Tests use `RefreshDatabase`/`migrate:fresh`, so this also verifies they apply cleanly.)

- [ ] **Step 6: Commit**

```bash
vendor/bin/pint
git add database/
git commit -m "Add max_players and per-player multiplayer columns"
```

---

## Task 2: GamePlayer model — left state and active scope

**Files:**
- Modify: `app/Domain/Game/Models/GamePlayer.php`
- Test: `tests/Unit/Game/GamePlayerTest.php` (create)

- [ ] **Step 1: Write the failing test**

```php
<?php

use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\GamePlayer;

it('reports whether a player has left', function (): void {
    $active = GamePlayer::factory()->create();
    $gone = GamePlayer::factory()->left('removed')->create();

    expect($active->hasLeft())->toBeFalse()
        ->and($gone->hasLeft())->toBeTrue();
});

it('scopes to active players only', function (): void {
    $game = Game::factory()->create();
    GamePlayer::factory()->for($game)->count(2)->create();
    GamePlayer::factory()->for($game)->left()->create();

    expect($game->gamePlayers()->active()->count())->toBe(2);
});
```

- [ ] **Step 2: Run it to confirm failure**

Run: `php artisan test --compact --filter=GamePlayerTest`
Expected: FAIL (`hasLeft()` / `active()` undefined).

- [ ] **Step 3: Implement on GamePlayer**

Add the three new columns to `casts()`:
```php
'consecutive_passes' => 'integer',
'left_at' => 'datetime',
```
Add methods (place near the other helpers):
```php
public function hasLeft(): bool
{
    return $this->left_at !== null;
}

public function scopeActive(Builder $query): Builder
{
    return $query->whereNull('left_at');
}
```
Add `use Illuminate\Database\Eloquent\Builder;` to the imports.

- [ ] **Step 4: Run the test to confirm pass**

Run: `php artisan test --compact --filter=GamePlayerTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
vendor/bin/pint
git add app/Domain/Game/Models/GamePlayer.php tests/Unit/Game/GamePlayerTest.php
git commit -m "Add left state and active scope to GamePlayer"
```

---

## Task 3: Game model — seat helpers and active-player helpers

**Files:**
- Modify: `app/Domain/Game/Models/Game.php`
- Test: `tests/Unit/Game/GameMultiplayerTest.php` (create)

- [ ] **Step 1: Write the failing test**

```php
<?php

use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\GamePlayer;
use App\Domain\User\Models\User;

it('has room for more players until max_players is reached', function (): void {
    $game = Game::factory()->pending()->create(['max_players' => 3]);
    GamePlayer::factory()->for($game)->count(2)->create();

    expect($game->fresh()->hasRoomForMorePlayers())->toBeTrue();

    GamePlayer::factory()->for($game)->create();

    expect($game->fresh()->hasRoomForMorePlayers())->toBeFalse();
});

it('identifies multiplayer games by seat count', function (): void {
    expect(Game::factory()->create(['max_players' => 2])->isMultiplayer())->toBeFalse()
        ->and(Game::factory()->create(['max_players' => 3])->isMultiplayer())->toBeTrue();
});

it('lists other active players excluding self and left players', function (): void {
    $game = Game::factory()->create(['max_players' => 3]);
    $me = User::factory()->create();
    $other = User::factory()->create();
    $gone = User::factory()->create();
    GamePlayer::factory()->for($game)->create(['user_id' => $me->id]);
    GamePlayer::factory()->for($game)->create(['user_id' => $other->id]);
    GamePlayer::factory()->for($game)->left()->create(['user_id' => $gone->id]);

    $others = $game->fresh()->otherActivePlayers($me);

    expect($others->pluck('id')->all())->toBe([$other->id]);
});
```

- [ ] **Step 2: Run it to confirm failure**

Run: `php artisan test --compact --filter=GameMultiplayerTest`
Expected: FAIL.

- [ ] **Step 3: Implement on Game**

Add `'max_players' => 'integer',` to `casts()`.

Replace the body of `canBeJoinedBy()`'s final return and `hasRoomForMorePlayers()`:
```php
public function canBeJoinedBy(User $user): bool
{
    if ($this->hasPlayer($user)) {
        return false;
    }

    if (! $this->isPending()) {
        return false;
    }

    return $this->hasRoomForMorePlayers();
}

public function hasRoomForMorePlayers(): bool
{
    return $this->gamePlayers()->count() < $this->max_players;
}
```

Add new helpers (near `getOpponent`):
```php
public function isMultiplayer(): bool
{
    return $this->max_players > 2;
}

/**
 * @return \Illuminate\Support\Collection<int, User>
 */
public function otherActivePlayers(User $user): \Illuminate\Support\Collection
{
    return $this->gamePlayers()
        ->active()
        ->with('user')
        ->get()
        ->reject(fn (GamePlayer $gp): bool => $gp->user_id === $user->id)
        ->map(fn (GamePlayer $gp): User => $gp->user)
        ->values();
}

public function activeGamePlayers(): \Illuminate\Support\Collection
{
    return $this->gamePlayers()->active()->orderBy('turn_order')->get();
}
```

Add a `pendingInvitations` relation alongside the existing `pendingInvitation`:
```php
public function pendingInvitations(): HasMany
{
    return $this->hasMany(GameInvitation::class)->where('status', 'pending');
}
```
(`HasMany` is already imported; `GameInvitation` is already imported.)

- [ ] **Step 4: Run the test to confirm pass**

Run: `php artisan test --compact --filter=GameMultiplayerTest`
Expected: PASS.

- [ ] **Step 5: Run the existing game model/feature tests to catch regressions**

Run: `php artisan test --compact --filter=Game`
Expected: PASS (2-player games default `max_players=2`, so `hasRoomForMorePlayers` still means "< 2").

- [ ] **Step 6: Commit**

```bash
vendor/bin/pint
git add app/Domain/Game/Models/Game.php tests/Unit/Game/GameMultiplayerTest.php
git commit -m "Add seat and active-player helpers to Game"
```

---

## Task 4: ActivateGameAction — extract game activation

Extracts the "make the game active, pick a random first player, set the timer, notify" logic so both auto-fill (Join) and manual Start reuse it. Only active (non-left) players are eligible to start.

**Files:**
- Create: `app/Domain/Game/Actions/ActivateGameAction.php`
- Test: `tests/Feature/Game/ActivateGameActionTest.php` (create)

- [ ] **Step 1: Write the failing test**

```php
<?php

use App\Domain\Game\Actions\ActivateGameAction;
use App\Domain\Game\Enums\GameStatus;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\GamePlayer;
use App\Domain\Game\Notifications\YourTurnNotification;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\Notification;

it('activates a pending game and assigns the turn to a player in the game', function (): void {
    Notification::fake();

    $game = Game::factory()->pending()->create(['max_players' => 3]);
    $users = User::factory()->count(3)->create();
    $users->each(fn (User $u, int $i) => GamePlayer::factory()->for($game)->create([
        'user_id' => $u->id,
        'turn_order' => $i + 1,
    ]));

    app(ActivateGameAction::class)->execute($game->fresh());

    $game->refresh();
    expect($game->status)->toBe(GameStatus::Active)
        ->and($game->current_turn_user_id)->toBeIn($users->pluck('id')->all())
        ->and($game->turn_expires_at)->not->toBeNull();

    Notification::assertSentTo(
        $users->firstWhere('id', $game->current_turn_user_id),
        YourTurnNotification::class
    );
});
```

- [ ] **Step 2: Run it to confirm failure**

Run: `php artisan test --compact --filter=ActivateGameActionTest`
Expected: FAIL (class missing).

- [ ] **Step 3: Implement ActivateGameAction**

```php
<?php

namespace App\Domain\Game\Actions;

use App\Domain\Game\Enums\GameStatus;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Notifications\YourTurnNotification;
use App\Domain\User\Models\User;

class ActivateGameAction
{
    public function execute(Game $game): Game
    {
        $firstPlayer = $game->gamePlayers()->active()->get()->random();

        $game->update([
            'status' => GameStatus::Active,
            'current_turn_user_id' => $firstPlayer->user_id,
            'turn_expires_at' => now()->addHours(Game::turnTimeoutHours()),
            'last_turn_reminder_sent' => null,
        ]);

        $freshGame = $game->fresh(['players', 'gamePlayers']);

        $firstPlayerUser = User::find($freshGame->current_turn_user_id);
        $firstPlayerUser->notify(new YourTurnNotification($freshGame));

        return $freshGame;
    }
}
```

- [ ] **Step 4: Run the test to confirm pass**

Run: `php artisan test --compact --filter=ActivateGameActionTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
vendor/bin/pint
git add app/Domain/Game/Actions/ActivateGameAction.php tests/Feature/Game/ActivateGameActionTest.php
git commit -m "Add ActivateGameAction"
```

---

## Task 5: JoinGameAction — sequential turn order, activate only when full

**Files:**
- Modify: `app/Domain/Game/Actions/JoinGameAction.php`
- Test: `tests/Feature/Game/JoinGameMultiplayerTest.php` (create)

- [ ] **Step 1: Write the failing tests**

```php
<?php

use App\Domain\Game\Actions\JoinGameAction;
use App\Domain\Game\Enums\GameStatus;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\GamePlayer;
use App\Domain\User\Models\User;
use App\Domain\Game\Support\TileBag;

beforeEach(function (): void {
    $this->creator = User::factory()->create();
});

function pendingGameWithCreator(User $creator, int $maxPlayers): Game
{
    $game = Game::factory()->pending()->create([
        'max_players' => $maxPlayers,
        'tile_bag' => TileBag::forLanguage('en')->toArray(),
    ]);
    GamePlayer::factory()->for($game)->create(['user_id' => $creator->id, 'turn_order' => 1]);

    return $game->fresh();
}

it('keeps a 3-player game pending until all seats fill and assigns sequential turn order', function (): void {
    $game = pendingGameWithCreator($this->creator, 3);
    $second = User::factory()->create();

    app(JoinGameAction::class)->execute($game, $second);
    $game->refresh();

    expect($game->status)->toBe(GameStatus::Pending)
        ->and($game->gamePlayers()->where('user_id', $second->id)->value('turn_order'))->toBe(2);

    $third = User::factory()->create();
    app(JoinGameAction::class)->execute($game->fresh(), $third);
    $game->refresh();

    expect($game->status)->toBe(GameStatus::Active)
        ->and($game->gamePlayers()->where('user_id', $third->id)->value('turn_order'))->toBe(3);
});

it('activates a 2-player game as soon as the second player joins', function (): void {
    $game = pendingGameWithCreator($this->creator, 2);
    app(JoinGameAction::class)->execute($game, User::factory()->create());

    expect($game->fresh()->status)->toBe(GameStatus::Active);
});
```

- [ ] **Step 2: Run to confirm failure**

Run: `php artisan test --compact --filter=JoinGameMultiplayerTest`
Expected: FAIL (turn_order hardcoded 2; activates on first join).

- [ ] **Step 3: Rewrite JoinGameAction**

Replace the body of `execute()` (keep `maybeGiveBlank`/`shouldGiveBlank` unchanged):
```php
public function execute(Game $game, User $user): Game
{
    /** @var User|null $creator */
    $creator = $game->players()->first();
    if ($creator?->id === $user->id) {
        throw GameException::cannotPlayAgainstSelf();
    }

    $tileBag = TileBag::fromArray($game->tile_bag);

    $turnOrder = $game->gamePlayers()->count() + 1;

    $gamePlayer = GamePlayer::create([
        'game_id' => $game->id,
        'user_id' => $user->id,
        'rack_tiles' => [],
        'score' => 0,
        'turn_order' => $turnOrder,
        'has_received_blank' => false,
    ]);

    $tiles = $tileBag->drawForRack([], 7);
    $tiles = $this->maybeGiveBlank($tiles, $gamePlayer, $tileBag);
    $gamePlayer->setRackTiles(TileBag::tilesToArray($tiles));

    $game->update(['tile_bag' => $tileBag->toArray()]);

    $freshGame = $game->fresh(['players', 'gamePlayers']);

    if ($freshGame->gamePlayers()->count() >= $freshGame->max_players) {
        return app(ActivateGameAction::class)->execute($freshGame);
    }

    return $freshGame;
}
```
Remove the now-unused `use Illuminate\Support\Lottery;`? No — `maybeGiveBlank` still uses it. Keep it. Remove the `YourTurnNotification` import only if unused (it is now unused here — activation handles it). Add `use App\Domain\Game\Actions\ActivateGameAction;` is unnecessary (same namespace). Remove `use App\Domain\Game\Enums\GameStatus;` if no longer referenced.

- [ ] **Step 4: Run the new tests + existing join tests**

Run: `php artisan test --compact --filter=JoinGame`
Expected: PASS. Investigate and fix any existing 2-player join test that assumed `random()` first-player side effects (behavior preserved via ActivateGameAction).

- [ ] **Step 5: Commit**

```bash
vendor/bin/pint
git add app/Domain/Game/Actions/JoinGameAction.php tests/Feature/Game/JoinGameMultiplayerTest.php
git commit -m "Generalize JoinGameAction to N players"
```

---

## Task 6: CreateGameAction + StoreGameRequest — accept max_players

**Files:**
- Modify: `app/Domain/Game/Actions/CreateGameAction.php`
- Modify: `app/Http/Requests/Game/StoreGameRequest.php`
- Modify: `app/Http/Controllers/Api/Game/StoreController.php`
- Test: `tests/Feature/Game/CreateGameMultiplayerTest.php` (create)

- [ ] **Step 1: Write the failing test**

```php
<?php

use App\Domain\Game\Actions\CreateGameAction;
use App\Domain\User\Models\User;

it('creates a game with the requested seat count', function (): void {
    $creator = User::factory()->create();

    $game = app(CreateGameAction::class)->execute($creator, 'en', maxPlayers: 4);

    expect($game->max_players)->toBe(4)
        ->and($game->gamePlayers()->count())->toBe(1);
});

it('defaults to two seats', function (): void {
    $game = app(CreateGameAction::class)->execute(User::factory()->create(), 'en');

    expect($game->max_players)->toBe(2);
});
```

- [ ] **Step 2: Run to confirm failure**

Run: `php artisan test --compact --filter=CreateGameMultiplayerTest`
Expected: FAIL (`maxPlayers` param missing).

- [ ] **Step 3: Add the parameter to CreateGameAction**

Add `int $maxPlayers = 2,` to the `execute()` signature (after `bool $isPublic = false`). Add `'max_players' => $maxPlayers,` to the `Game::create([...])` array.

- [ ] **Step 4: Add validation + wire the controller**

In `StoreGameRequest::rules()` add:
```php
'max_players' => ['sometimes', 'integer', 'between:2,4'],
```
In `StoreController::__invoke`, pass it to the action:
```php
$game = app(CreateGameAction::class)->execute(
    $request->user(),
    $request->validated('language', 'nl'),
    $request->validated('opponent_username'),
    $request->validated('board_type', 'standard'),
    $request->validated('board_template'),
    $request->boolean('is_public'),
    (int) $request->validated('max_players', 2),
);
```

- [ ] **Step 5: Run the test + existing create tests**

Run: `php artisan test --compact --filter=CreateGame`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
vendor/bin/pint
git add app/Domain/Game/Actions/CreateGameAction.php app/Http/Requests/Game/StoreGameRequest.php app/Http/Controllers/Api/Game/StoreController.php tests/Feature/Game/CreateGameMultiplayerTest.php
git commit -m "Accept max_players when creating a game"
```

---

## Task 7: StartGameAction + route — manual early start

**Files:**
- Create: `app/Domain/Game/Actions/StartGameAction.php`
- Create: `app/Http/Controllers/Api/Game/StartController.php`
- Modify: `routes/api.php`
- Test: `tests/Feature/Game/StartGameTest.php` (create)

- [ ] **Step 1: Write the failing tests**

```php
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
```

- [ ] **Step 2: Run to confirm failure**

Run: `php artisan test --compact --filter=StartGameTest`
Expected: FAIL.

- [ ] **Step 3: Add an exception factory**

In `app/Domain/Game/Exceptions/GameException.php`, add (match the file's existing static-factory style):
```php
public static function notEnoughPlayersToStart(): self
{
    return new self('At least two players are needed to start the game.');
}

public static function gameAlreadyStarted(): self
{
    return new self('This game has already started.');
}
```

- [ ] **Step 4: Implement StartGameAction**

```php
<?php

namespace App\Domain\Game\Actions;

use App\Domain\Game\Exceptions\GameException;
use App\Domain\Game\Models\Game;
use App\Domain\User\Enums\InvitationStatus;
use App\Domain\User\Models\User;

class StartGameAction
{
    public function execute(Game $game, User $actor): Game
    {
        if (! $game->isPending()) {
            throw GameException::gameAlreadyStarted();
        }

        if (! $game->isCreator($actor)) {
            throw GameException::onlyCreatorCanStart();
        }

        if ($game->gamePlayers()->count() < 2) {
            throw GameException::notEnoughPlayersToStart();
        }

        $game->pendingInvitations()->update(['status' => InvitationStatus::Declined]);

        return app(ActivateGameAction::class)->execute($game->fresh());
    }
}
```
Also add `onlyCreatorCanStart()` to `GameException` (message: "Only the game creator can start the game.").

- [ ] **Step 5: Add controller + route**

Create `StartController`:
```php
<?php

namespace App\Http\Controllers\Api\Game;

use App\Domain\Game\Actions\StartGameAction;
use App\Domain\Game\Models\Game;
use App\Http\Resources\GameResource;
use Illuminate\Http\Request;

class StartController
{
    public function __invoke(Request $request, Game $game): GameResource
    {
        $game = app(StartGameAction::class)->execute($game, $request->user());

        return new GameResource($game);
    }
}
```
In `routes/api.php`, inside the `games` group, add:
```php
Route::post('{game}/start', Game\StartController::class)->middleware('throttle:game-action');
```

- [ ] **Step 6: Run tests**

Run: `php artisan test --compact --filter=StartGameTest`
Expected: PASS.

- [ ] **Step 7: Commit**

```bash
vendor/bin/pint
git add app/Domain/Game/Actions/StartGameAction.php app/Http/Controllers/Api/Game/StartController.php routes/api.php app/Domain/Game/Exceptions/GameException.php tests/Feature/Game/StartGameTest.php
git commit -m "Add manual StartGameAction and route"
```

---

## Task 8: InvitePlayerAction — allow multiple pending invites up to open seats

**Files:**
- Modify: `app/Domain/Game/Actions/InvitePlayerAction.php`
- Test: `tests/Feature/Game/InviteMultiplayerTest.php` (create)

- [ ] **Step 1: Write the failing tests**

```php
<?php

use App\Domain\Game\Actions\InvitePlayerAction;
use App\Domain\Game\Exceptions\GameException;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\GamePlayer;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\Notification;

beforeEach(fn () => Notification::fake());

it('allows inviting multiple players up to the open seats', function (): void {
    $creator = User::factory()->create();
    $game = Game::factory()->pending()->create(['max_players' => 3]);
    GamePlayer::factory()->for($game)->create(['user_id' => $creator->id, 'turn_order' => 1]);

    app(InvitePlayerAction::class)->execute($game->fresh(), User::factory()->create());
    app(InvitePlayerAction::class)->execute($game->fresh(), User::factory()->create());

    expect($game->fresh()->pendingInvitations()->count())->toBe(2);
});

it('refuses to invite beyond the open seats', function (): void {
    $creator = User::factory()->create();
    $game = Game::factory()->pending()->create(['max_players' => 2]);
    GamePlayer::factory()->for($game)->create(['user_id' => $creator->id, 'turn_order' => 1]);

    app(InvitePlayerAction::class)->execute($game->fresh(), User::factory()->create());

    app(InvitePlayerAction::class)->execute($game->fresh(), User::factory()->create());
})->throws(GameException::class);
```

- [ ] **Step 2: Run to confirm failure**

Run: `php artisan test --compact --filter=InviteMultiplayerTest`
Expected: the second test FAILS (today only one invite was allowed via the `canBeInvitedToBy`/`hasRoomForMorePlayers` gate, but the per-seat cap counting pending invitations is new). First test may already fail depending on current gating.

- [ ] **Step 3: Add a seat-aware guard to InvitePlayerAction**

Add a check in `validateInvitation()` (after the existing checks):
```php
$openSeats = $game->max_players
    - $game->gamePlayers()->count()
    - $game->pendingInvitations()->count();

if ($openSeats <= 0) {
    throw GameException::noOpenSeats();
}
```
Add `noOpenSeats()` to `GameException` (message: "There are no open seats left in this game."). The existing single-invitation restriction (if any) that blocks a second invite must be removed so multiple distinct invitees are allowed; keep the `invitationAlreadyExists()` guard for the *same* invitee.

- [ ] **Step 4: Run tests**

Run: `php artisan test --compact --filter=InviteMultiplayerTest`
Expected: PASS. Also run `php artisan test --compact --filter=Invite` to catch regressions in existing invite tests.

- [ ] **Step 5: Commit**

```bash
vendor/bin/pint
git add app/Domain/Game/Actions/InvitePlayerAction.php app/Domain/Game/Exceptions/GameException.php tests/Feature/Game/InviteMultiplayerTest.php
git commit -m "Allow multiple pending invites up to open seats"
```

---

## Task 9: SwitchTurnAction — rotate over active players, skipping left

**Files:**
- Modify: `app/Domain/Game/Actions/SwitchTurnAction.php`
- Test: `tests/Feature/Game/SwitchTurnMultiplayerTest.php` (create)

- [ ] **Step 1: Write the failing tests**

```php
<?php

use App\Domain\Game\Actions\SwitchTurnAction;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\GamePlayer;
use App\Domain\User\Models\User;

function activeThreePlayerGame(): array
{
    $users = User::factory()->count(3)->create();
    $game = Game::factory()->active()->create([
        'max_players' => 3,
        'current_turn_user_id' => $users[0]->id,
    ]);
    $users->each(fn (User $u, int $i) => GamePlayer::factory()->for($game)->create([
        'user_id' => $u->id, 'turn_order' => $i + 1,
    ]));

    return [$game->fresh(), $users];
}

it('advances in turn order and wraps around', function (): void {
    [$game, $users] = activeThreePlayerGame();

    app(SwitchTurnAction::class)->execute($game);
    expect($game->fresh()->current_turn_user_id)->toBe($users[1]->id);

    app(SwitchTurnAction::class)->execute($game->fresh());
    expect($game->fresh()->current_turn_user_id)->toBe($users[2]->id);

    app(SwitchTurnAction::class)->execute($game->fresh());
    expect($game->fresh()->current_turn_user_id)->toBe($users[0]->id);
});

it('skips a player who has left', function (): void {
    [$game, $users] = activeThreePlayerGame();
    $game->gamePlayers()->where('user_id', $users[1]->id)->update(['left_at' => now()]);

    app(SwitchTurnAction::class)->execute($game->fresh());

    expect($game->fresh()->current_turn_user_id)->toBe($users[2]->id);
});
```

- [ ] **Step 2: Run to confirm failure**

Run: `php artisan test --compact --filter=SwitchTurnMultiplayerTest`
Expected: the "skips" test FAILS (current logic does not skip left players).

- [ ] **Step 3: Rewrite SwitchTurnAction**

```php
<?php

namespace App\Domain\Game\Actions;

use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\GamePlayer;

class SwitchTurnAction
{
    public function execute(Game $game): void
    {
        $players = $game->gamePlayers()->orderBy('turn_order')->get();
        $currentIndex = $players->search(fn (GamePlayer $p): bool => $p->user_id === $game->current_turn_user_id);

        $count = $players->count();
        $nextPlayer = null;

        for ($step = 1; $step <= $count; $step++) {
            $candidate = $players[($currentIndex + $step) % $count];

            if (! $candidate->hasLeft()) {
                $nextPlayer = $candidate;
                break;
            }
        }

        if (! $nextPlayer instanceof GamePlayer) {
            return;
        }

        $game->update([
            'current_turn_user_id' => $nextPlayer->user_id,
            'turn_expires_at' => now()->addHours(Game::turnTimeoutHours()),
            'last_turn_reminder_sent' => null,
        ]);
    }
}
```

- [ ] **Step 4: Run tests**

Run: `php artisan test --compact --filter=SwitchTurnMultiplayerTest`
Expected: PASS. Then `php artisan test --compact --filter=Game` for regressions.

- [ ] **Step 5: Commit**

```bash
vendor/bin/pint
git add app/Domain/Game/Actions/SwitchTurnAction.php tests/Feature/Game/SwitchTurnMultiplayerTest.php
git commit -m "Skip left players in turn rotation"
```

---

## Task 10: RemovePlayerAction — mark left, advance or end

**Files:**
- Create: `app/Domain/Game/Actions/RemovePlayerAction.php`
- Test: `tests/Feature/Game/RemovePlayerTest.php` (create)

- [ ] **Step 1: Write the failing tests**

```php
<?php

use App\Domain\Game\Actions\RemovePlayerAction;
use App\Domain\Game\Enums\GameStatus;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\GamePlayer;
use App\Domain\User\Models\User;

function activeThreePlayerGameForRemoval(): array
{
    $users = User::factory()->count(3)->create();
    $game = Game::factory()->active()->create([
        'max_players' => 3,
        'current_turn_user_id' => $users[0]->id,
        'tile_bag' => [['letter' => 'A', 'points' => 1]],
    ]);
    $users->each(fn (User $u, int $i) => GamePlayer::factory()->for($game)->create([
        'user_id' => $u->id, 'turn_order' => $i + 1, 'rack_tiles' => [['letter' => 'B', 'points' => 3]],
    ]));

    return [$game->fresh(), $users];
}

it('marks the player left, discards their tiles, and advances the turn', function (): void {
    [$game, $users] = activeThreePlayerGameForRemoval();

    app(RemovePlayerAction::class)->execute($game, $users[0], 'removed');
    $game->refresh();

    $removed = $game->gamePlayers()->where('user_id', $users[0]->id)->first();
    expect($removed->hasLeft())->toBeTrue()
        ->and($removed->left_reason)->toBe('removed')
        ->and($removed->rack_tiles)->toBe([])
        ->and($game->status)->toBe(GameStatus::Active)
        ->and($game->current_turn_user_id)->toBe($users[1]->id);
});

it('ends the game when only one active player remains', function (): void {
    [$game, $users] = activeThreePlayerGameForRemoval();

    app(RemovePlayerAction::class)->execute($game->fresh(), $users[0], 'removed');
    app(RemovePlayerAction::class)->execute($game->fresh(), $users[1], 'removed');
    $game->refresh();

    expect($game->status)->toBe(GameStatus::Finished)
        ->and($game->winner_id)->toBe($users[2]->id);
});
```

- [ ] **Step 2: Run to confirm failure**

Run: `php artisan test --compact --filter=RemovePlayerTest`
Expected: FAIL (class missing).

- [ ] **Step 3: Implement RemovePlayerAction**

```php
<?php

namespace App\Domain\Game\Actions;

use App\Domain\Game\Models\Game;
use App\Domain\User\Models\User;

class RemovePlayerAction
{
    public function execute(Game $game, User $user, string $reason): void
    {
        $gamePlayer = $game->getGamePlayer($user);

        if (! $gamePlayer || $gamePlayer->hasLeft()) {
            return;
        }

        $gamePlayer->update([
            'left_at' => now(),
            'left_reason' => $reason,
            'rack_tiles' => [],
        ]);

        if ($game->fresh()->gamePlayers()->active()->count() <= 1) {
            app(EndGameAction::class)->execute($game->fresh());

            return;
        }

        app(SwitchTurnAction::class)->execute($game->fresh());
    }
}
```
This relies on Task 14 making `EndGameAction` choose the winner among active players. Implement Task 14 before relying on the "ends the game" test; if executing strictly in order, the second test here may need Task 14 — note this dependency and, if needed, run only the first test now and the second after Task 14.

- [ ] **Step 4: Run the first test now**

Run: `php artisan test --compact --filter="RemovePlayerTest" `
Expected: the "advances the turn" test PASSES. The "ends the game" test passes once Task 14 is complete (re-run then).

- [ ] **Step 5: Commit**

```bash
vendor/bin/pint
git add app/Domain/Game/Actions/RemovePlayerAction.php tests/Feature/Game/RemovePlayerTest.php
git commit -m "Add RemovePlayerAction"
```

---

## Task 11: PassAction + AutoPassAction — per-player pass counter and removal

**Files:**
- Modify: `app/Domain/Game/Actions/PassAction.php`
- Modify: `app/Domain/Game/Actions/AutoPassAction.php`
- Test: `tests/Feature/Game/PassRemovalTest.php` (create)

- [ ] **Step 1: Write the failing tests**

```php
<?php

use App\Domain\Game\Actions\PassAction;
use App\Domain\Game\Enums\GameStatus;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\GamePlayer;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\Notification;

beforeEach(fn () => Notification::fake());

function threePlayerActiveGame(): array
{
    $users = User::factory()->count(3)->create();
    $game = Game::factory()->active()->create([
        'max_players' => 3,
        'current_turn_user_id' => $users[0]->id,
        'tile_bag' => [['letter' => 'A', 'points' => 1]],
    ]);
    $users->each(fn (User $u, int $i) => GamePlayer::factory()->for($game)->create([
        'user_id' => $u->id, 'turn_order' => $i + 1, 'rack_tiles' => [['letter' => 'B', 'points' => 3]],
    ]));

    return [$game->fresh(), $users];
}

it('removes a player after two of their own consecutive passes in a 3-player game', function (): void {
    [$game, $users] = threePlayerActiveGame();

    // Round 1: each passes once
    app(PassAction::class)->execute($game->fresh(), $users[0]);
    app(PassAction::class)->execute($game->fresh(), $users[1]);
    app(PassAction::class)->execute($game->fresh(), $users[2]);

    // user[0] passes a second consecutive time -> removed
    app(PassAction::class)->execute($game->fresh(), $users[0]);
    $game->refresh();

    expect($game->gamePlayers()->where('user_id', $users[0]->id)->first()->hasLeft())->toBeTrue()
        ->and($game->current_turn_user_id)->toBe($users[1]->id);
});

it('does not remove players in a 2-player game (ends on four passes instead)', function (): void {
    $users = User::factory()->count(2)->create();
    $game = Game::factory()->active()->create([
        'max_players' => 2,
        'current_turn_user_id' => $users[0]->id,
        'tile_bag' => [['letter' => 'A', 'points' => 1]],
    ]);
    $users->each(fn (User $u, int $i) => GamePlayer::factory()->for($game)->create([
        'user_id' => $u->id, 'turn_order' => $i + 1, 'rack_tiles' => [['letter' => 'B', 'points' => 3]],
    ]));

    app(PassAction::class)->execute($game->fresh(), $users[0]);
    app(PassAction::class)->execute($game->fresh(), $users[1]);
    app(PassAction::class)->execute($game->fresh(), $users[0]);
    app(PassAction::class)->execute($game->fresh(), $users[1]);

    $game->refresh();
    expect($game->status)->toBe(GameStatus::Finished)
        ->and($game->gamePlayers()->whereNotNull('left_at')->count())->toBe(0);
});
```

- [ ] **Step 2: Run to confirm failure**

Run: `php artisan test --compact --filter=PassRemovalTest`
Expected: FAIL.

- [ ] **Step 3: Update PassAction**

After `Move::create([...])` and `$game->increment('consecutive_passes');`, add per-player tracking and the removal branch. Replace the section from the increment through `handleEndGameOrSwitchTurn`:
```php
$game->increment('consecutive_passes');

$gamePlayer = $game->getGamePlayer($user);
$gamePlayer->increment('consecutive_passes');

if ($game->isMultiplayer() && $gamePlayer->fresh()->consecutive_passes >= 2) {
    app(RemovePlayerAction::class)->execute($game->fresh(), $user, 'removed');
} else {
    $this->handleEndGameOrSwitchTurn($game, $ruleEngine);
}
```
Add `use App\Domain\Game\Actions\RemovePlayerAction;` (same namespace, so the `use` is unnecessary — reference directly).

- [ ] **Step 4: Update AutoPassAction the same way**

In `AutoPassAction::execute`, after `$game->increment('consecutive_passes');`:
```php
$gamePlayer = $game->getGamePlayer($timedOutUser);
$gamePlayer->increment('consecutive_passes');

if ($game->isMultiplayer() && $gamePlayer->fresh()->consecutive_passes >= 2) {
    app(RemovePlayerAction::class)->execute($game->fresh(), $timedOutUser, 'removed');
} else {
    $this->handleEndGameOrSwitchTurn($game);
}
```

- [ ] **Step 5: Run tests**

Run: `php artisan test --compact --filter=PassRemovalTest`
Expected: PASS. Then `php artisan test --compact --filter=Pass` for regressions.

- [ ] **Step 6: Commit**

```bash
vendor/bin/pint
git add app/Domain/Game/Actions/PassAction.php app/Domain/Game/Actions/AutoPassAction.php tests/Feature/Game/PassRemovalTest.php
git commit -m "Remove players after two consecutive passes in multiplayer games"
```

---

## Task 12: Reset per-player pass counter on play and swap

**Files:**
- Modify: `app/Domain/Game/Actions/PlayMoveAction.php`
- Modify: `app/Domain/Game/Actions/SwapTilesAction.php`
- Test: `tests/Feature/Game/PassCounterResetTest.php` (create)

- [ ] **Step 1: Write the failing test**

```php
<?php

use App\Domain\Game\Actions\PassAction;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\GamePlayer;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\Notification;

beforeEach(fn () => Notification::fake());

it('resets a player consecutive pass counter when they swap', function (): void {
    $users = User::factory()->count(3)->create();
    $game = Game::factory()->active()->create([
        'max_players' => 3,
        'current_turn_user_id' => $users[0]->id,
        'tile_bag' => array_fill(0, 10, ['letter' => 'A', 'points' => 1]),
    ]);
    $users->each(fn (User $u, int $i) => GamePlayer::factory()->for($game)->create([
        'user_id' => $u->id, 'turn_order' => $i + 1, 'rack_tiles' => [['letter' => 'B', 'points' => 3]],
    ]));

    $game->gamePlayers()->where('user_id', $users[0]->id)->update(['consecutive_passes' => 1]);

    app(\App\Domain\Game\Actions\SwapTilesAction::class)
        ->execute($game->fresh(), $users[0], [['letter' => 'B', 'points' => 3]]);

    expect($game->gamePlayers()->where('user_id', $users[0]->id)->value('consecutive_passes'))->toBe(0);
});
```
(Adjust the `SwapTilesAction::execute` argument shape to match its real signature — confirm by reading the file; the tiles param is the rack tiles to swap.)

- [ ] **Step 2: Run to confirm failure**

Run: `php artisan test --compact --filter=PassCounterResetTest`
Expected: FAIL (counter not reset).

- [ ] **Step 3: Reset the counter in both actions**

In `PlayMoveAction::execute` and `SwapTilesAction::execute`, immediately after the `$game->getGamePlayer($user)` (or wherever the acting `GamePlayer` is available) and before/after the move is recorded, reset:
```php
$game->getGamePlayer($user)->update(['consecutive_passes' => 0]);
```
Also reset the game-level counter as today if the existing code does so (PlayMove/Swap should already reset `consecutive_passes` to 0 on the game; if not, add `$game->update(['consecutive_passes' => 0]);`). Verify against current behavior so two-player end-on-passes is unaffected.

- [ ] **Step 4: Run tests**

Run: `php artisan test --compact --filter=PassCounterResetTest`
Expected: PASS. Then `php artisan test --compact --filter="PlayMove|Swap"`.

- [ ] **Step 5: Commit**

```bash
vendor/bin/pint
git add app/Domain/Game/Actions/PlayMoveAction.php app/Domain/Game/Actions/SwapTilesAction.php tests/Feature/Game/PassCounterResetTest.php
git commit -m "Reset per-player pass counter on play and swap"
```

---

## Task 13: ConsecutivePassRule — two-player games only

**Files:**
- Modify: `app/Domain/Game/Support/Rules/EndGame/ConsecutivePassRule.php`
- Test: `tests/Unit/Game/ConsecutivePassRuleTest.php` (create)

- [ ] **Step 1: Write the failing test**

```php
<?php

use App\Domain\Game\Enums\MoveType;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\GamePlayer;
use App\Domain\Game\Models\Move;
use App\Domain\Game\Support\Rules\EndGame\ConsecutivePassRule;
use App\Domain\User\Models\User;

it('never ends a 3-player game on consecutive passes (removal handles it)', function (): void {
    $game = Game::factory()->active()->create(['max_players' => 3]);
    User::factory()->count(3)->create()->each(
        fn (User $u, int $i) => GamePlayer::factory()->for($game)->create(['user_id' => $u->id, 'turn_order' => $i + 1])
    );
    foreach (range(1, 6) as $n) {
        Move::create(['game_id' => $game->id, 'user_id' => $game->players()->first()->id, 'type' => MoveType::Pass, 'score' => 0, 'tiles' => null, 'words' => null]);
    }

    expect((new ConsecutivePassRule)->shouldEndGame($game->fresh()))->toBeFalse();
});
```

- [ ] **Step 2: Run to confirm failure**

Run: `php artisan test --compact --filter=ConsecutivePassRuleTest`
Expected: FAIL (rule fires on 4 passes regardless of player count).

- [ ] **Step 3: Guard the rule**

In `shouldEndGame()`, add at the top:
```php
if ($game->isMultiplayer()) {
    return false;
}
```

- [ ] **Step 4: Run tests**

Run: `php artisan test --compact --filter=ConsecutivePassRuleTest`
Expected: PASS. Then run existing end-game tests: `php artisan test --compact --filter=EndGame`.

- [ ] **Step 5: Commit**

```bash
vendor/bin/pint
git add app/Domain/Game/Support/Rules/EndGame/ConsecutivePassRule.php tests/Unit/Game/ConsecutivePassRuleTest.php
git commit -m "Disable consecutive-pass end rule for multiplayer games"
```

---

## Task 14: EndGameAction — winner among active players, ties → draw

**Files:**
- Modify: `app/Domain/Game/Actions/EndGameAction.php`
- Test: `tests/Feature/Game/EndGameMultiplayerTest.php` (create)

- [ ] **Step 1: Write the failing tests**

```php
<?php

use App\Domain\Game\Actions\EndGameAction;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\GamePlayer;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\Notification;

beforeEach(fn () => Notification::fake());

it('picks the highest-scoring active player as winner, ignoring left players', function (): void {
    $users = User::factory()->count(3)->create();
    $game = Game::factory()->active()->create(['max_players' => 3, 'tile_bag' => []]);
    GamePlayer::factory()->for($game)->create(['user_id' => $users[0]->id, 'turn_order' => 1, 'score' => 100, 'rack_tiles' => []]);
    GamePlayer::factory()->for($game)->create(['user_id' => $users[1]->id, 'turn_order' => 2, 'score' => 150, 'rack_tiles' => []]);
    GamePlayer::factory()->for($game)->left()->create(['user_id' => $users[2]->id, 'turn_order' => 3, 'score' => 999, 'rack_tiles' => []]);

    app(EndGameAction::class)->execute($game);

    expect($game->fresh()->winner_id)->toBe($users[1]->id);
});

it('records a draw when the top active score is tied', function (): void {
    $users = User::factory()->count(3)->create();
    $game = Game::factory()->active()->create(['max_players' => 3, 'tile_bag' => []]);
    GamePlayer::factory()->for($game)->create(['user_id' => $users[0]->id, 'turn_order' => 1, 'score' => 150, 'rack_tiles' => []]);
    GamePlayer::factory()->for($game)->create(['user_id' => $users[1]->id, 'turn_order' => 2, 'score' => 150, 'rack_tiles' => []]);
    GamePlayer::factory()->for($game)->create(['user_id' => $users[2]->id, 'turn_order' => 3, 'score' => 90, 'rack_tiles' => []]);

    app(EndGameAction::class)->execute($game);

    expect($game->fresh()->winner_id)->toBeNull();
});
```

- [ ] **Step 2: Run to confirm failure**

Run: `php artisan test --compact --filter=EndGameMultiplayerTest`
Expected: FAIL (current `determineWinner` returns highest score across all players including left, never null).

- [ ] **Step 3: Update EndGameAction**

Replace `determineWinner` and the `update([...])` in `execute()` so the winner is `null` on a tie and left players are excluded:
```php
public function execute(Game $game): void
{
    $gamePlayers = $game->gamePlayers()->with('user')->get();

    $this->applyEndGamePenalties($gamePlayers);

    $winnerPlayer = $this->determineWinner($gamePlayers->fresh());

    $game->update([
        'status' => GameStatus::Finished,
        'winner_id' => $winnerPlayer?->user_id,
    ]);

    // ... rest unchanged (guest check, stats, achievements, notify) ...
}

private function determineWinner(Collection $gamePlayers): ?GamePlayer
{
    $contenders = $gamePlayers->reject(fn (GamePlayer $gp): bool => $gp->hasLeft());

    if ($contenders->isEmpty()) {
        $contenders = $gamePlayers; // all left (degenerate) — fall back to all
    }

    $sorted = $contenders->sortByDesc('score')->values();
    $top = $sorted->first();

    $tiedForFirst = $sorted->filter(fn (GamePlayer $gp): bool => $gp->score === $top->score);

    if ($tiedForFirst->count() > 1) {
        return null;
    }

    return $top;
}
```
Note: `$gamePlayers->fresh()` — if `Collection::fresh()` is unavailable, re-query: `$game->gamePlayers()->with('user')->get()` after penalties. Keep `Collection` import. The down-to-one-active case (from RemovePlayerAction) yields a single contender, who wins.

- [ ] **Step 4: Run tests**

Run: `php artisan test --compact --filter=EndGameMultiplayerTest`
Expected: PASS. Re-run the deferred RemovePlayerTest "ends the game" case: `php artisan test --compact --filter=RemovePlayerTest` — both PASS now. Then `php artisan test --compact --filter=EndGame`.

- [ ] **Step 5: Commit**

```bash
vendor/bin/pint
git add app/Domain/Game/Actions/EndGameAction.php tests/Feature/Game/EndGameMultiplayerTest.php
git commit -m "Determine winner among active players, draw on tie"
```

---

## Task 15: ResignAction — removal behavior in multiplayer

**Files:**
- Modify: `app/Domain/Game/Actions/ResignAction.php`
- Test: `tests/Feature/Game/ResignMultiplayerTest.php` (create)

- [ ] **Step 1: Write the failing tests**

```php
<?php

use App\Domain\Game\Actions\ResignAction;
use App\Domain\Game\Enums\GameStatus;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\GamePlayer;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\Notification;

beforeEach(fn () => Notification::fake());

it('removes a resigning player in a 3-player game and continues', function (): void {
    $users = User::factory()->count(3)->create();
    $game = Game::factory()->active()->create([
        'max_players' => 3, 'current_turn_user_id' => $users[0]->id, 'tile_bag' => [['letter' => 'A', 'points' => 1]],
    ]);
    $users->each(fn (User $u, int $i) => GamePlayer::factory()->for($game)->create([
        'user_id' => $u->id, 'turn_order' => $i + 1, 'rack_tiles' => [['letter' => 'B', 'points' => 3]],
    ]));

    app(ResignAction::class)->execute($game->fresh(), $users[0]);
    $game->refresh();

    expect($game->status)->toBe(GameStatus::Active)
        ->and($game->gamePlayers()->where('user_id', $users[0]->id)->first()->left_reason)->toBe('resigned')
        ->and($game->current_turn_user_id)->toBe($users[1]->id);
});

it('keeps two-player resignation behavior (opponent wins immediately)', function (): void {
    $users = User::factory()->count(2)->create();
    $game = Game::factory()->active()->create([
        'max_players' => 2, 'current_turn_user_id' => $users[0]->id,
    ]);
    $users->each(fn (User $u, int $i) => GamePlayer::factory()->for($game)->create(['user_id' => $u->id, 'turn_order' => $i + 1]));

    app(ResignAction::class)->execute($game->fresh(), $users[0]);
    $game->refresh();

    expect($game->status)->toBe(GameStatus::Finished)
        ->and($game->winner_id)->toBe($users[1]->id);
});
```

- [ ] **Step 2: Run to confirm failure**

Run: `php artisan test --compact --filter=ResignMultiplayerTest`
Expected: the 3-player test FAILS (current ResignAction finishes the game immediately).

- [ ] **Step 3: Branch ResignAction on player count**

At the start of `execute()` after validation and before creating the Resign move, branch:
```php
if ($game->isMultiplayer()) {
    $move = Move::create([
        'game_id' => $game->id,
        'user_id' => $user->id,
        'tiles' => null,
        'words' => null,
        'score' => 0,
        'type' => MoveType::Resign,
    ]);

    app(RemovePlayerAction::class)->execute($game->fresh(), $user, 'resigned');

    $freshGame = $game->fresh(['gamePlayers.user', 'currentTurnUser', 'winner', 'latestMove.user', 'players']);
    broadcast(new MovePlayed($freshGame, $move, $user))->toOthers();

    return $move;
}
```
Leave the existing two-player resignation logic below this branch unchanged.

- [ ] **Step 4: Run tests**

Run: `php artisan test --compact --filter=ResignMultiplayerTest`
Expected: PASS. Then `php artisan test --compact --filter=Resign`.

- [ ] **Step 5: Commit**

```bash
vendor/bin/pint
git add app/Domain/Game/Actions/ResignAction.php tests/Feature/Game/ResignMultiplayerTest.php
git commit -m "Resignation removes player in multiplayer games"
```

---

## Task 16: MovePlayed — broadcast to every other player

**Files:**
- Modify: `app/Domain/Game/Events/MovePlayed.php`
- Test: `tests/Unit/Game/MovePlayedTest.php` (create)

- [ ] **Step 1: Write the failing test**

```php
<?php

use App\Domain\Game\Events\MovePlayed;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\GamePlayer;
use App\Domain\Game\Models\Move;
use App\Domain\Game\Enums\MoveType;
use App\Domain\User\Models\User;

it('broadcasts on the game channel and every other players user channel', function (): void {
    $users = User::factory()->count(3)->create();
    $game = Game::factory()->active()->create(['max_players' => 3]);
    $users->each(fn (User $u, int $i) => GamePlayer::factory()->for($game)->create(['user_id' => $u->id, 'turn_order' => $i + 1]));
    $game->load('gamePlayers.user');

    $move = Move::create(['game_id' => $game->id, 'user_id' => $users[0]->id, 'type' => MoveType::Pass, 'score' => 0, 'tiles' => null, 'words' => null]);

    $channels = collect((new MovePlayed($game, $move, $users[0]))->broadcastOn())
        ->map(fn ($c) => $c->name)
        ->all();

    expect($channels)->toContain('private-game.'.$game->ulid)
        ->and($channels)->toContain('private-user.'.$users[1]->ulid)
        ->and($channels)->toContain('private-user.'.$users[2]->ulid)
        ->and($channels)->not->toContain('private-user.'.$users[0]->ulid);
});
```

- [ ] **Step 2: Run to confirm failure**

Run: `php artisan test --compact --filter=MovePlayedTest`
Expected: FAIL (only one opponent channel today).

- [ ] **Step 3: Update broadcastOn**

```php
public function broadcastOn(): array
{
    $channels = [
        new PrivateChannel('game.'.$this->game->ulid),
    ];

    $this->game->gamePlayers
        ->reject(fn ($gp): bool => $gp->user_id === $this->player->id)
        ->each(function ($gp) use (&$channels): void {
            $channels[] = new PrivateChannel('user.'.$gp->user->ulid);
        });

    return $channels;
}
```

- [ ] **Step 4: Run tests**

Run: `php artisan test --compact --filter=MovePlayedTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
vendor/bin/pint
git add app/Domain/Game/Events/MovePlayed.php tests/Unit/Game/MovePlayedTest.php
git commit -m "Broadcast MovePlayed to all other players"
```

---

## Task 17: MultiplayerEloCalculator — pairwise averaged rating change

**Files:**
- Create: `app/Domain/User/Support/EloCalculator/MultiplayerEloCalculator.php`
- Test: `tests/Unit/User/MultiplayerEloCalculatorTest.php` (create)

- [ ] **Step 1: Write the failing tests**

```php
<?php

use App\Domain\User\Support\EloCalculator\MultiplayerEloCalculator;

it('returns the average pairwise change for a clear win over two equal opponents', function (): void {
    $calc = new MultiplayerEloCalculator(kFactor: 32, scaleFactor: 400);

    // beats two 1500-rated opponents, self at 1500
    $change = $calc->netChange(1500, [
        ['elo' => 1500, 'score' => 1.0],
        ['elo' => 1500, 'score' => 1.0],
    ]);

    // each pairwise: 32 * (1 - 0.5) = 16; average of (16,16) = 16
    expect($change)->toBe(16);
});

it('nets a win and a loss against equal opponents to roughly zero', function (): void {
    $calc = new MultiplayerEloCalculator(kFactor: 32, scaleFactor: 400);

    $change = $calc->netChange(1500, [
        ['elo' => 1500, 'score' => 1.0],
        ['elo' => 1500, 'score' => 0.0],
    ]);

    // (16 + -16) / 2 = 0
    expect($change)->toBe(0);
});

it('treats a tie as half a point', function (): void {
    $calc = new MultiplayerEloCalculator(kFactor: 32, scaleFactor: 400);

    $change = $calc->netChange(1500, [['elo' => 1500, 'score' => 0.5]]);

    expect($change)->toBe(0);
});
```

- [ ] **Step 2: Run to confirm failure**

Run: `php artisan test --compact --filter=MultiplayerEloCalculatorTest`
Expected: FAIL.

- [ ] **Step 3: Implement the calculator**

```php
<?php

declare(strict_types=1);

namespace App\Domain\User\Support\EloCalculator;

class MultiplayerEloCalculator
{
    private readonly int $kFactor;

    private readonly int $scaleFactor;

    public function __construct(?int $kFactor = null, ?int $scaleFactor = null)
    {
        $this->kFactor = $kFactor ?? config('game.elo.k_factor', 32);
        $this->scaleFactor = $scaleFactor ?? config('game.elo.scale_factor', 400);
    }

    /**
     * @param  array<int, array{elo: int, score: float}>  $matchups
     */
    public function netChange(int $playerElo, array $matchups): int
    {
        if ($matchups === []) {
            return 0;
        }

        $total = 0.0;

        foreach ($matchups as $matchup) {
            $expected = 1 / (1 + 10 ** (($matchup['elo'] - $playerElo) / $this->scaleFactor));
            $total += $this->kFactor * ($matchup['score'] - $expected);
        }

        return (int) round($total / count($matchups));
    }
}
```

- [ ] **Step 4: Run tests**

Run: `php artisan test --compact --filter=MultiplayerEloCalculatorTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
vendor/bin/pint
git add app/Domain/User/Support/EloCalculator/MultiplayerEloCalculator.php tests/Unit/User/MultiplayerEloCalculatorTest.php
git commit -m "Add MultiplayerEloCalculator"
```

---

## Task 18: UpdateGameEndStatsAction — multiplayer stats branch

**Files:**
- Modify: `app/Domain/Game/Actions/Stats/UpdateGameEndStatsAction.php`
- Test: `tests/Feature/Game/Stats/MultiplayerStatsTest.php` (create)

- [ ] **Step 1: Write the failing tests**

```php
<?php

use App\Domain\Game\Actions\Stats\UpdateGameEndStatsAction;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\GamePlayer;
use App\Domain\User\Models\EloHistory;
use App\Domain\User\Models\HeadToHeadStats;
use App\Domain\User\Models\User;
use App\Domain\User\Models\UserStatistics;

function finishedThreePlayerGame(array $scores): array
{
    $users = collect($scores)->map(fn () => User::factory()->create(['elo_rating' => 1500]));
    $game = Game::factory()->finished()->create(['max_players' => 3]);
    $users->each(fn (User $u, int $i) => GamePlayer::factory()->for($game)->create([
        'user_id' => $u->id, 'turn_order' => $i + 1, 'score' => array_values($scores)[$i], 'rack_tiles' => [],
    ]));
    $game->update(['winner_id' => $users[collect($scores)->keys()->sortByDesc(fn ($k) => $scores[$k])->first()]->id]);

    return [$game->fresh(['gamePlayers.user']), $users];
}

it('records elo history and head-to-head for every pair in a 3-player game', function (): void {
    [$game, $users] = finishedThreePlayerGame([200, 150, 100]);

    app(UpdateGameEndStatsAction::class)->execute($game);

    // one elo history row per player
    expect(EloHistory::where('game_id', $game->id)->count())->toBe(3);
    // head-to-head: 3 unordered pairs -> 6 directed rows
    expect(HeadToHeadStats::count())->toBe(6);
    // winner counters
    expect(UserStatistics::where('user_id', $users[0]->id)->value('games_won'))->toBe(1)
        ->and(UserStatistics::where('user_id', $users[2]->id)->value('games_lost'))->toBe(1);
});

it('still uses single-pair logic for two-player games', function (): void {
    $users = User::factory()->count(2)->create(['elo_rating' => 1500]);
    $game = Game::factory()->finished()->create(['max_players' => 2, 'winner_id' => $users[0]->id]);
    $users->each(fn (User $u, int $i) => GamePlayer::factory()->for($game)->create(['user_id' => $u->id, 'turn_order' => $i + 1, 'score' => 150 - $i * 50]));

    app(UpdateGameEndStatsAction::class)->execute($game->fresh(['gamePlayers.user']));

    expect(EloHistory::where('game_id', $game->id)->count())->toBe(2);
});
```
(Confirm the `HeadToHeadStats` and `UserStatistics` and `EloHistory` namespaces/columns by reading those models; adjust assertions to real column names — `games_won`, `games_lost`, `games_draw` are confirmed from `UpdateGameEndStatsAction`.)

- [ ] **Step 2: Run to confirm failure**

Run: `php artisan test --compact --filter=MultiplayerStatsTest`
Expected: the 3-player test FAILS (current action early-returns for `count !== 2`).

- [ ] **Step 3: Replace the early return with a branch**

In `execute()`, replace:
```php
if ($gamePlayers->count() !== 2) {
    return;
}
```
with:
```php
if ($gamePlayers->count() === 2) {
    $this->updateTwoPlayerStats($game, $gamePlayers);

    return;
}

$this->updateMultiplayerStats($game, $gamePlayers);
```
Move the existing two-player body (the `updatePlayerStats` / `updateWinnerSpecificStats` / `updateEloRatings` / `updateHeadToHeadRecords` calls and the `statsCache = []` reset) into a new private method `updateTwoPlayerStats(Game $game, Collection $gamePlayers): void`.

- [ ] **Step 4: Implement the multiplayer branch**

Add these private methods. The comparator defines pairwise outcomes: active beats left; otherwise higher score wins; equal within a group is a tie. Ratings are snapshotted before any mutation.
```php
private function updateMultiplayerStats(Game $game, Collection $gamePlayers): void
{
    $winner = $game->winner;

    foreach ($gamePlayers as $gamePlayer) {
        $stats = $this->getStats($gamePlayer->user);
        $this->updateGameScore($stats, $gamePlayer->score);
        $this->updateWinLossDraw($stats, $gamePlayer->user, $winner);
        $this->updateWinStreak($stats, $gamePlayer->user, $winner);
        $stats->save();
    }

    $this->updateMultiplayerElo($game, $gamePlayers);
    $this->updateMultiplayerHeadToHead($gamePlayers);

    $this->statsCache = [];
}

private function updateMultiplayerElo(Game $game, Collection $gamePlayers): void
{
    $calculator = app(\App\Domain\User\Support\EloCalculator\MultiplayerEloCalculator::class);

    $ratingsBefore = $gamePlayers->mapWithKeys(
        fn (GamePlayer $gp): array => [$gp->user_id => $gp->user->elo_rating]
    );

    foreach ($gamePlayers as $gamePlayer) {
        $matchups = $gamePlayers
            ->reject(fn (GamePlayer $other): bool => $other->user_id === $gamePlayer->user_id)
            ->map(fn (GamePlayer $other): array => [
                'elo' => $ratingsBefore[$other->user_id],
                'score' => $this->pairwiseScore($gamePlayer, $other),
            ])
            ->values()
            ->all();

        $before = $ratingsBefore[$gamePlayer->user_id];
        $after = $before + $calculator->netChange($before, $matchups);

        $this->recordEloChange($gamePlayer->user, $game, $before, $after);
        $gamePlayer->user->update(['elo_rating' => $after]);
        $this->updateEloExtremes($gamePlayer->user, $after);
    }
}

private function pairwiseScore(GamePlayer $a, GamePlayer $b): float
{
    if ($a->hasLeft() !== $b->hasLeft()) {
        return $a->hasLeft() ? 0.0 : 1.0;
    }

    if ($a->score === $b->score) {
        return 0.5;
    }

    return $a->score > $b->score ? 1.0 : 0.0;
}

private function updateMultiplayerHeadToHead(Collection $gamePlayers): void
{
    $players = $gamePlayers->values();

    for ($i = 0; $i < $players->count(); $i++) {
        for ($j = $i + 1; $j < $players->count(); $j++) {
            $this->recordHeadToHeadPair($players[$i], $players[$j]);
        }
    }
}

private function recordHeadToHeadPair(GamePlayer $a, GamePlayer $b): void
{
    $h2hA = HeadToHeadStats::firstOrCreate(['user_id' => $a->user_id, 'opponent_id' => $b->user_id]);
    $h2hB = HeadToHeadStats::firstOrCreate(['user_id' => $b->user_id, 'opponent_id' => $a->user_id]);

    $h2hA->total_score_for += $a->score;
    $h2hA->total_score_against += $b->score;
    $h2hB->total_score_for += $b->score;
    $h2hB->total_score_against += $a->score;

    $score = $this->pairwiseScore($a, $b);

    if ($score === 0.5) {
        $h2hA->draws++;
        $h2hB->draws++;
    } elseif ($score === 1.0) {
        $h2hA->wins++;
        $h2hB->losses++;
    } else {
        $h2hA->losses++;
        $h2hB->wins++;
    }

    $h2hA->save();
    $h2hB->save();
}
```

- [ ] **Step 5: Run tests**

Run: `php artisan test --compact --filter=MultiplayerStatsTest`
Expected: PASS. Then `php artisan test --compact --filter=Stats` for regressions (two-player path must be unchanged).

- [ ] **Step 6: Commit**

```bash
vendor/bin/pint
git add app/Domain/Game/Actions/Stats/UpdateGameEndStatsAction.php tests/Feature/Game/Stats/MultiplayerStatsTest.php
git commit -m "Add multiplayer stats branch with pairwise ELO and head-to-head"
```

---

## Task 19: Resources — N-player serialization

**Files:**
- Modify: `app/Http/Resources/GameResource.php`
- Modify: `app/Http/Resources/GameListResource.php`
- Modify: `app/Http/Resources/PendingGameResource.php`
- Test: `tests/Feature/Game/GameResourceMultiplayerTest.php` (create)

- [ ] **Step 1: Write the failing tests**

```php
<?php

use App\Http\Resources\GameListResource;
use App\Http\Resources\GameResource;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\GamePlayer;
use App\Domain\User\Models\User;
use Illuminate\Http\Request;

it('exposes max_players and per-player left state on the game resource', function (): void {
    $users = User::factory()->count(3)->create();
    $game = Game::factory()->active()->create(['max_players' => 3, 'current_turn_user_id' => $users[0]->id]);
    GamePlayer::factory()->for($game)->create(['user_id' => $users[0]->id, 'turn_order' => 1]);
    GamePlayer::factory()->for($game)->create(['user_id' => $users[1]->id, 'turn_order' => 2]);
    GamePlayer::factory()->for($game)->left('removed')->create(['user_id' => $users[2]->id, 'turn_order' => 3]);

    $request = Request::create('/'); $request->setUserResolver(fn () => $users[0]);
    $array = (new GameResource($game->fresh(['gamePlayers.user', 'currentTurnUser'])))->toArray($request);

    expect($array['max_players'])->toBe(3)
        ->and($array['players'])->toHaveCount(3)
        ->and(collect($array['players'])->firstWhere('ulid', $users[2]->ulid)['has_left'])->toBeTrue();
});

it('returns all players (not a single opponent) on the list resource', function (): void {
    $users = User::factory()->count(3)->create();
    $game = Game::factory()->active()->create(['max_players' => 3, 'current_turn_user_id' => $users[0]->id]);
    $users->each(fn (User $u, int $i) => GamePlayer::factory()->for($game)->create(['user_id' => $u->id, 'turn_order' => $i + 1, 'score' => ($i + 1) * 10]));

    $request = Request::create('/'); $request->setUserResolver(fn () => $users[0]);
    $array = (new GameListResource($game->fresh(['gamePlayers.user', 'players'])))->toArray($request);

    expect($array['players'])->toHaveCount(3)
        ->and($array['my_score'])->toBe(10)
        ->and($array['max_players'])->toBe(3);
});
```

- [ ] **Step 2: Run to confirm failure**

Run: `php artisan test --compact --filter=GameResourceMultiplayerTest`
Expected: FAIL.

- [ ] **Step 3: Update GameResource**

Add `'max_players' => $this->max_players,` to the array, and add per-player fields inside the `players` map:
```php
'turn_order' => $gp->turn_order,
'has_left' => $gp->hasLeft(),
'left_reason' => $gp->left_reason,
```

- [ ] **Step 4: Rewrite GameListResource for N players**

Replace the single-opponent shape with a players array, keeping `my_score`/`is_my_turn` for convenience:
```php
public function toArray(Request $request): array
{
    $user = $request->user();
    $myGamePlayer = $this->gamePlayers->firstWhere('user_id', $user->id);

    return [
        'ulid' => $this->ulid,
        'language' => $this->language,
        'status' => $this->status->value,
        'max_players' => $this->max_players,
        'players' => $this->gamePlayers
            ->sortBy('turn_order')
            ->values()
            ->map(fn ($gp): array => [
                'ulid' => $gp->user->ulid,
                'username' => $gp->user->username,
                'avatar' => $gp->user->avatar,
                'avatar_color' => $gp->user->avatar_color,
                'score' => $gp->score,
                'is_current_turn' => $this->current_turn_user_id === $gp->user_id,
                'is_me' => $gp->user_id === $user->id,
                'has_left' => $gp->hasLeft(),
            ]),
        'my_score' => $myGamePlayer?->score ?? 0,
        'is_my_turn' => $this->current_turn_user_id === $user->id,
        'winner_ulid' => $this->winner?->ulid,
        'updated_at' => $this->updated_at,
        'last_move_description' => $this->resource->getLastMoveDescription($user, $this->resource->getOpponent($user)),
        'turn_expires_at' => $this->resource->getTurnExpiresAt()?->toISOString(),
        'pending_invitation' => $this->formatPendingInvitation(),
        'is_public' => $this->is_public,
    ];
}
```
Keep `formatPendingInvitation()` as-is. (`getLastMoveDescription` still works with a single "other" reference for the summary text; multiplayer descriptions already name the actor.)

- [ ] **Step 5: Update PendingGameResource**

Add seat info:
```php
return [
    'ulid' => $this->ulid,
    'language' => $this->language,
    'creator' => $this->players->first()?->username,
    'max_players' => $this->max_players,
    'players_joined' => $this->gamePlayers->count(),
    'created_at' => $this->created_at,
];
```

- [ ] **Step 6: Run tests**

Run: `php artisan test --compact --filter=GameResourceMultiplayerTest`
Expected: PASS. Then run the broader API tests: `php artisan test --compact --filter="Resource|Index|Show|Pending"`.

- [ ] **Step 7: Commit**

```bash
vendor/bin/pint
git add app/Http/Resources/ tests/Feature/Game/GameResourceMultiplayerTest.php
git commit -m "Serialize games for N players"
```

---

## Task 20: Turn reminder — warn about impending removal

**Files:**
- Modify: `app/Domain/Game/Notifications/TurnReminderNotification.php`
- Test: `tests/Unit/Game/TurnReminderRemovalWarningTest.php` (create)

- [ ] **Step 1: Write the failing test**

```php
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

    expect($message->body)->toContain('removed');
});
```
(Confirm `ExpoMessage` exposes `->body` for assertion; if not, assert on a small extracted method instead — e.g. extract the removal-warning text into a public/testable method and assert that.)

- [ ] **Step 2: Run to confirm failure**

Run: `php artisan test --compact --filter=TurnReminderRemovalWarningTest`
Expected: FAIL.

- [ ] **Step 3: Add the removal-warning branch**

In `getBody()` (or `toExpo`), before composing the normal message, check whether the notifiable is one pass away from removal:
```php
$gamePlayer = $this->game->getGamePlayer($notifiable);

if ($this->game->isMultiplayer() && $gamePlayer && $gamePlayer->consecutive_passes >= 1) {
    return 'Play now or you will be removed from the game if your turn times out again.';
}
```
Wire `$notifiable` through to where the body is built (the method already receives `$notifiable` in `toExpo`).

- [ ] **Step 4: Run tests**

Run: `php artisan test --compact --filter=TurnReminderRemovalWarningTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
vendor/bin/pint
git add app/Domain/Game/Notifications/TurnReminderNotification.php tests/Unit/Game/TurnReminderRemovalWarningTest.php
git commit -m "Warn about removal in turn reminders"
```

---

## Task 21: Test helper for N-player games + full suite

**Files:**
- Modify: `tests/Helpers/TestHelpers.php`
- Test: run the whole suite.

- [ ] **Step 1: Add createGameWithNPlayers helper**

```php
/**
 * @param  array<int, User>|null  $players
 */
function createGameWithNPlayers(
    int $count,
    ?array $players = null,
    GameStatus $status = GameStatus::Active,
    ?string $language = 'en',
): Game {
    $players ??= User::factory()->count($count)->create()->all();

    $game = Game::factory()->create([
        'status' => $status,
        'language' => $language,
        'max_players' => $count,
        'current_turn_user_id' => $players[0]->id,
        'board_state' => createEmptyBoard(),
        'tile_bag' => createDefaultTileBag(),
    ]);

    foreach ($players as $i => $player) {
        GamePlayer::factory()->create([
            'game_id' => $game->id,
            'user_id' => $player->id,
            'turn_order' => $i + 1,
            'rack_tiles' => createDefaultRack(),
            'score' => 0,
        ]);
    }

    return $game->fresh(['players', 'gamePlayers']);
}
```

- [ ] **Step 2: Run the entire test suite**

Run: `php artisan test --compact`
Expected: PASS. Fix any regressions (most likely: existing tests that constructed games without `max_players` now default to 2, which is correct; any test asserting old single-opponent list payload must be updated to the players array).

- [ ] **Step 3: Run Larastan + Pint**

Run: `vendor/bin/pint && vendor/bin/phpstan analyse --memory-limit=2G` (use the project's configured analyse command if different).
Expected: no new errors introduced by these changes.

- [ ] **Step 4: Commit**

```bash
git add tests/
git commit -m "Add N-player test helper and finalize backend multiplayer support"
```

---

## Self-Review Notes (for the executor)

- **Task ordering dependency:** Task 10 (RemovePlayerAction) "ends the game" assertion depends on Task 14 (EndGameAction winner-among-active). Execute Task 14 before treating Task 10 as fully green, or run Task 10's first test only and revisit.
- **Two-player invariants:** Tasks 5, 11, 13, 15, 18 each include a two-player regression test. If any two-player behavior changes, stop — the spec requires it stays identical.
- **Confirm before coding:** the exact signatures of `SwapTilesAction::execute`, `HeadToHeadStats` columns (`total_score_for`, `total_score_against`, `wins`, `losses`, `draws`), `UserStatistics` columns, `EloHistory` columns, and `GameException` factory style — all referenced above. Read each file before writing the step that touches it.
- **Spec coverage check:** seats + manual start (Tasks 6,7), removal on double-pass (Tasks 10,11), 2-player unchanged (regression tests throughout), resignation-as-removal (Task 15), ties→draw (Task 14), pairwise averaged ELO + head-to-head + counters (Tasks 17,18), narrative stats skipped in multiplayer (Task 18 omits them), broadcast to all (Task 16), empty-rack rule unchanged (covered by existing tests; verified no change needed), serialization (Task 19), removal warning (Task 20). The EmptyRackRule needs no code change per the spec; add an N-player end-via-empty-rack feature test if not already covered by existing EndGame tests.
