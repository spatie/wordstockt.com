# 3-4 Player Support — Design

Date: 2026-06-06
Repos: `wordstockt.com` (Laravel backend, this repo) and `../wordstockt-app` (React Native client)

## Summary

Today a game is strictly two players: created by one user, joined by exactly one opponent, and the UI shows a "You vs. opponent" bar. This work generalizes games to **2, 3, or 4 players**.

The backend is already largely multiplayer-shaped (the `game_players` pivot has a `turn_order`, turn switching uses modulo arithmetic, the winner is the highest score). The work is to remove the hardcoded "2" assumptions, add a seat count and a per-player "left" state, add a manual-start path, generalize the end-game and stats logic, broadcast to every player, and rebuild the client's score bar, types, creation, and invitation flows around a player array instead of a single `opponent`.

Two-player games must behave exactly as they do today. The new mechanics (player removal on double-pass, pairwise ELO across 3+ players) apply **only** to 3-4 player games.

## Decisions (locked during brainstorming)

1. **Seat count and start.** The creator picks the target size (2-4) at creation. The game auto-activates when all seats fill. The creator may also **manually start** early once at least 2 players have joined; manual start closes the remaining pending invitations and locks the roster/turn order at the current count. Applies to public games too (creator sets the seat count when making a public game).
2. **Player removal on double-pass (3-4 player games only).** A player is removed when they pass on **two of their own consecutive turns** (manual pass or automatic turn-timeout pass both count; a swap is a real move and resets the counter). The player is warned before the removing pass. On removal: their tiles are discarded, they are marked **left**, and remaining players continue. When only one active player remains, that player wins immediately.
3. **Two-player games unchanged.** They keep the existing end rules: four consecutive passes ends the game (winner by score), resignation makes the opponent win immediately. No removal mechanic.
4. **End-game scoring is unchanged and already per-player.** Flat +25 "clear rack" bonus to whoever empties their rack with an empty bag; each player is penalized their own remaining tile value at game end; winner is the highest score. Nothing to generalize here beyond letting the empty-rack end condition fire with any player count.
5. **Resignation in 3-4 player games behaves like removal:** resigner is marked left, tiles discarded, others continue, last player standing wins. Two-player resignation is unchanged.
6. **Ties for first place** record a draw (no single `winner_id`): tied leaders share 1st in results, get `games_draw++`, win streak reset, no ELO winner. Matches existing two-player draw handling.
7. **Stats / rating (pairwise ELO).** For 3-4 player games, treat the final ranking as a set of pairwise 1v1 results, compute each pair's ELO delta, and apply each player the **average** of their pairwise deltas (divide by `N-1`) so a multiplayer game moves a rating about as much as one normal game. Record pairwise **head-to-head** for every pair, plus the simple counters (games played/won/lost, total/highest score, win streak). The narrative stats (biggest comeback, closest victory, first-move win) stay **two-player only**. Removed/left players participate in the ranking as the lowest place(s), ordered among themselves by frozen score.
8. **Client layout.** A single horizontal row of **equal player chips** (avatar, name, score, the two existing status dots, turn highlight), scaling 2→4. The "in bag" tile count moves out of the center into the left of the move-description row. The layout is keyed to the game's **original roster**: when a player leaves, the layout does **not** revert to the 2-player bar; the departed player keeps a dimmed cell with a "LEFT" tag and frozen score, and the turn rotation skips them.

## Data model changes (backend)

### `games` table
- Add `max_players` (unsigned tinyint, default 2). The target seat count chosen at creation. Existing rows backfill to 2.
- Keep `consecutive_passes` for the two-player end rule. Per-player pass tracking for removal lives on `game_players` (below).
- `winner_id` stays nullable single-winner (null = draw or not finished).

### `game_players` table
- Add `consecutive_passes` (unsigned tinyint, default 0): reset to 0 on any non-pass move by that player, incremented on a pass/auto-pass. Used only to trigger removal in 3-4 player games.
- Add `left_at` (nullable timestamp) and `left_reason` (nullable string: `removed` | `resigned`). A non-null `left_at` means the player is out; their score is frozen and they are skipped by turn rotation and never marked current turn.
- `turn_order` already exists and is the basis for rotation.

`GamePlayer` gets a `hasLeft(): bool` helper and a query scope `active()` (`whereNull('left_at')`).

## Backend behavior changes

### Creation, joining, starting
- **`CreateGameAction`**: accept `max_players` (2-4, default 2) and an optional list of opponent usernames to invite (replacing the single opponent). Creator is `turn_order` 1. Game starts `pending`.
- **`JoinGameAction`**: assign `turn_order = current gamePlayers count + 1` (not hardcoded 2). Draw the joining player's rack as today. **Do not** auto-activate on every join; instead activate only when `gamePlayers count === max_players`. Activation logic (pick random first player, set `active`, set `turn_expires_at`, notify) moves into a shared `ActivateGameAction` used by both auto-fill and manual start.
- **`StartGameAction` (new)** + `StartController` + route `POST games/{game}/start`: creator-only, allowed when `pending` and `gamePlayers count >= 2`. Cancels remaining pending invitations, then calls `ActivateGameAction`.
- **`Game::canBeJoinedBy` / `hasRoomForMorePlayers`**: replace `< 2` with `< max_players`.
- **`InvitePlayerAction`**: allow multiple simultaneous pending invitations, up to the remaining open seats (`max_players - gamePlayers count - pendingInvitations count`). Remove the single-invitation restriction.
- **`Game::pendingInvitation` (singular `HasOne`)**: add a `pendingInvitations` `HasMany` for the multi-invite case; keep the singular accessor working for two-player.

### Turn rotation and removal
- **`SwitchTurnAction`**: rotate over **active** players only (`orderBy('turn_order')` filtered by `whereNull('left_at')`), keeping modulo arithmetic. If exactly one active player remains after a removal, end the game (that player wins).
- **`RemovePlayerAction` (new)**: marks a `GamePlayer` as left (`left_at`, `left_reason`), discards their `rack_tiles` (set to `[]`), and either ends the game (one active player left) or advances the turn past them. Emits an event/move so the client can narrate it.
- **`PassAction`**: increment that player's `game_players.consecutive_passes`. In a **3-4 player game**, if it reaches 2, invoke `RemovePlayerAction` instead of the normal switch. In a **two-player game**, keep incrementing the game-level `consecutive_passes` and the existing end-game-on-4-passes path unchanged.
- **`AutoPassAction`** (turn-timeout): same per-player increment and removal trigger as `PassAction`; an auto-pass counts toward the two consecutive passes.
- **`PlayMoveAction` / `SwapTilesAction`**: reset that player's `game_players.consecutive_passes` to 0 (a swap and a play are both "not a pass").

### End-game rules
- **`EmptyRackRule`**: needs no logic change. Its two branches already generalize: "2 or more players have emptied their racks → end" is valid for any N, and the single-empty-rack branch ("a player emptied their rack and it is no longer their turn") preserves today's exact timing for 2 players and behaves sensibly for 3-4. Add tests at N=3/4 to confirm; do not alter the semantics (so two-player timing stays identical).
- **`ConsecutivePassRule`**: applies to **two-player games only** (guard on `gamePlayers count === 2`). In 3-4 player games the removal mechanic supersedes it (repeated passing removes players one by one until a single survivor wins).
- **`EndGameAction`**: unchanged scoring (`applyEndGamePenalties` already loops all players; `determineWinner` already uses `sortByDesc('score')`). Add: if the top score is tied between 2+ active players, set `winner_id = null` (draw). Left players are excluded from the winner determination but remain in the game for ranking/results.

### Broadcasting and notifications
- **`MovePlayed::broadcastOn`**: replace the single-opponent lookup with a loop broadcasting to every **other** player's `user.{ulid}` channel (plus the existing `game.{ulid}` channel).
- **`PassAction::notifyOpponent` / similar**: notify the next active player whose turn it now is, rather than "the opponent." The turn-timeout reminder messaging is extended so that when an active player is one pass away from removal (3-4 player game, their `consecutive_passes === 1`), the reminder states they will be removed if they miss/pass again.
- **`getOpponent`** stays for two-player paths. Add `Game::otherActivePlayers(User): Collection` for the N-player notification/broadcast paths. Audit and update the other call sites flagged in exploration (`PlayMoveAction`, `SwapTilesAction`, `AutoPassAction`, `ResignAction`).

### `ResignAction`
- Two-player: unchanged (opponent wins immediately).
- 3-4 player: call `RemovePlayerAction` with reason `resigned`; if one active player remains, that player wins.

### Stats (`UpdateGameEndStatsAction`)
- Remove the `count !== 2` early return; branch on player count.
- **Two-player branch**: exactly today's behavior (single-pair ELO, head-to-head, narrative stats).
- **3-4 player branch**:
  - Build a final ranking: active/finished players by score desc, then left players by frozen score desc below them. Ties share a rank.
  - **Pairwise ELO**: for each unordered pair, actual score is 1 / 0 / 0.5 by rank (0.5 on equal rank). Compute each side's delta via the existing ELO expected-score formula, accumulate per player, then apply `round(sum_of_deltas / (N - 1))` to each player's rating. Record one `EloHistory` row per player capturing the net change for the game. Update ELO extremes.
  - **Head-to-head**: record/update `HeadToHeadStats` for every pair (wins/losses/draws + score-for/against) consistent with the pairwise outcome.
  - **Simple counters**: games played/won/lost (winner = sole rank-1 player; draw when rank 1 is shared), total/highest score, win streak (increment for a sole 1st, reset otherwise).
  - **Skip** biggest comeback, closest victory, first-move win.
- A new `MultiplayerEloCalculator` (or an added method on the ELO support) encapsulates the pairwise+average computation with tie handling, unit-tested independently.

### Tile supply
No change needed: the standard bag (100 tiles) covers 4 racks of 7 (28) with margin. Racks are drawn at join time as today.

## Client changes (`../wordstockt-app`)

### Types and schema
- `src/types/game.ts`: `Game.players: Player[]` already exists; add `maxPlayers: number` and a per-player `hasLeft: boolean` (+ optional `leftReason`). Replace `GameListItem.opponent`/`opponentScore` with `players: GameListPlayer[]` (lightweight: ulid, username, avatar, color, score, isCurrentTurn, hasLeft) and keep `myScore`/`isMyTurn` derived for convenience.
- `src/schemas/game.schema.ts`: update `GameListItemSchema` to the players array; add `max_players` and per-player `has_left` to the game/player schemas.

### Score bar (`src/components/game/ScoreBar.tsx`)
- Rebuild as an **equal-chip row** over `game.players` ordered by `turn_order`: avatar, name ("You" for self), score, the two existing status dots, amber-red turn highlight on the current player.
- Move the "in bag" badge into the left of the move-description row.
- Left players: dimmed (~55%), greyed avatar, frozen muted score, a "LEFT" tag in place of the status dots, never highlighted. Layout is keyed to the original roster length, never reverting to the two-player layout.
- Optional follow-up (not required for v1): per-player turn-timer as a thin progress line on the active cell.

### Games list (`src/components/game-list/GameCard.tsx`)
- Replace single-opponent display with a compact multi-player summary (names/avatars of the other players; scores shown compactly, e.g. "You 197 · GE 289 · TO 176"). Handle multiple pending invitees ("Waiting for 2 players...").

### Creation and invitations
Invitations stay a **post-creation** step, exactly as today: the create sheet does not pick opponents; the game is created first and players are invited afterward from the pending game.
- `CreateGameModal.tsx`: the only new control is a player-count selector (2/3/4), plus a one-line note that players are invited after creating. Picking 2 is byte-for-byte today's flow.
- `InvitePlayerModal.tsx`: the existing modal, generalized — invite players one at a time until the open seats are filled (button flips to "Invited"; disabled when full), show a seat-progress indicator, and for the creator surface the **Start now** action when `pending` and ≥2 joined. No longer capped at a single invite.
- `src/api/queries/useGames.ts`: `CreateGameParams` gains `max_players`. The existing optional `opponent_username` becomes `opponent_usernames?: string[]`, used only by the programmatic rematch path (not the create sheet).
- `src/api/queries/useInvitations.ts`: allow inviting multiple users (sequential calls are fine); add a start-game mutation.
- `src/hooks/useRematch.ts`: rematch recreates a game with the same roster and `max_players`.
- `src/hooks/useFilteredGames.ts`: rename opponent-centric fields to player-centric; filtering logic (your-turn / their-turn / awaiting) stays the same shape.

### Real-time
`useWebSocket` already refetches game state on `move.played`; no change needed beyond the server broadcasting to all players (handled backend-side).

## Testing

Backend (Pest, feature tests preferred):
- New helper `createGameWithNPlayers(int $count, ...)` alongside the existing `createGameWithPlayers`.
- Creation with `max_players` 3/4; join assigns sequential `turn_order`; auto-activate only when full; manual start with a partial roster cancels pending invites and locks the roster.
- Turn rotation skips left players; modulo wraps correctly with 3 and 4.
- Removal on two consecutive passes (manual and auto-pass mix); swap resets the counter; down-to-one ends the game with that player winning.
- Two-player games: unchanged end-on-4-passes, resignation, and stats (regression).
- Empty-rack end with 3-4 players; flat +25 to the player who clears; per-player penalties; winner by score; tie → draw (`winner_id` null).
- Pairwise ELO: unit tests on the calculator (sum/average, tie at 0.5, removed players ranked last); feature test asserting per-player net change and one `EloHistory` row each.
- Head-to-head recorded for every pair; narrative stats skipped for 3-4 player games.
- `MovePlayed` broadcasts to every other player's channel.

Client:
- Type/schema parsing of an N-player game payload.
- ScoreBar rendering for 2/3/4 players and the left-player state (snapshot or RTL assertions).
- Create/invite flow allows the chosen number of opponents; rematch reuses the roster.

## Out of scope (explicitly)

- Reworking the 1v1 ELO ladder or adding a separate multiplayer leaderboard.
- Generalizing the narrative stats (comeback, closest victory, first-move win) to 3-4 players.
- Per-player turn-timer line in the score bar (optional follow-up).
- Re-filling a seat after a game has started (no late join once active).
