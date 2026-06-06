# 3-4 Player Support (Mobile App) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Update the React Native client (`/Users/freek/dev/code/wordstockt-app`) to support 2-4 player games against the new backend API, replacing the single-`opponent` model with a players array.

**Architecture:** The backend now serializes games for N players. The client mirrors this: types/schemas carry a `players` array everywhere (no more `opponent`), the score bar becomes an equal-chip row keyed to the original roster (dimming "left" players), the games list shows all players' scores, the create sheet gains a Players (2/3/4) selector, and invites happen from empty score-bar seats with a "Start now" action.

**Tech Stack:** React Native 0.76+, Expo, TypeScript (strict), TanStack Query, Zustand, Zod, React Native Paper. Tests: Jest (`npm test`). Types: `npx tsc --noEmit`. Format: `npx prettier --write <files>`. Lint: `npm run lint`.

**Repo / branch:** Work in `/Users/freek/dev/code/wordstockt-app` on branch `freekmurze/multiplayer-3-4-players` (already created). **Do NOT switch/rename branches.** Commit per task.

**Spec:** `/Users/freek/conductor/workspaces/wordstockt.com/dublin/docs/superpowers/specs/2026-06-06-3-4-player-support-design.md`

## New backend API shape (source of truth)

`GameResource` (game detail) — `players[]` now each include `turn_order`, `has_left`, `left_reason`; the game includes `max_players`:
```json
{ "max_players": 3, "players": [ { "ulid": "..", "username": "..", "avatar": null, "avatar_color": null, "score": 0, "rack_count": 7, "is_current_turn": true, "turn_order": 1, "has_left": false, "left_reason": null, "has_free_swap": false, "has_received_blank": false, "received_empty_rack_bonus": false } ], ... }
```

`GameListResource` (games list) — NO more `opponent`/`opponent_score`; now a `players[]` array + `max_players`:
```json
{ "ulid":"..","language":"en","status":"active","max_players":3,
  "players":[ {"ulid":"..","username":"..","avatar":null,"avatar_color":null,"score":10,"is_current_turn":true,"is_me":true,"has_left":false} ],
  "my_score":10,"is_my_turn":true,"winner_ulid":null,"updated_at":"..","last_move_description":"..","turn_expires_at":null,"pending_invitation":null,"is_public":false }
```

`PendingGameResource` — adds `max_players`, `players_joined`.

`POST games/{ulid}/start` — new endpoint; returns a `GameResource`.
`POST games` — accepts `max_players` (2-4).

---

## File Structure

- `src/types/game.ts` — `Player` gains `turnOrder`/`hasLeft`/`leftReason`; `Game` gains `maxPlayers`; `GameListItem` replaces `opponent`/`opponentScore` with `players: GameListPlayer[]` + `maxPlayers`; add `GameListPlayer`; `PendingGame` gains `maxPlayers`/`playersJoined`.
- `src/schemas/game.schema.ts` — matching Zod schemas + transforms.
- `src/api/queries/useGames.ts` — `CreateGameParams.max_players`; add `useStartGame`.
- `src/api/queries/useInvitations.ts` — confirm multi-invite works (per-user calls).
- `src/components/game-list/CreateGameModal.tsx` — Players (2/3/4) selector.
- `src/components/game/ScoreBar.tsx` — N-player equal-chip row, left treatment, pending seats, Start now.
- `src/components/game-list/GameCard.tsx` — multiplayer list display.
- `src/hooks/useFilteredGames.ts`, `src/hooks/useRematch.ts` — player-centric.
- Tests under `src/**/__tests__/`.

---

## Task 1: Types + Zod schemas for N players

**Files:** `src/types/game.ts`, `src/schemas/game.schema.ts`, test `src/schemas/__tests__/game.schema.test.ts` (create).

- [ ] **Step 1: Write failing schema tests**

Create `src/schemas/__tests__/game.schema.test.ts`:
```ts
import { transformGame, transformGameListItem } from '../game.schema';

const baseListPayload = {
  ulid: 'g1', language: 'en', status: 'active' as const, max_players: 3,
  players: [
    { ulid: 'u1', username: 'You', avatar: null, avatar_color: null, score: 10, is_current_turn: true, is_me: true, has_left: false },
    { ulid: 'u2', username: 'Jess', avatar: null, avatar_color: null, score: 20, is_current_turn: false, is_me: false, has_left: false },
    { ulid: 'u3', username: 'Tom', avatar: null, avatar_color: null, score: 5, is_current_turn: false, is_me: false, has_left: true },
  ],
  my_score: 10, is_my_turn: true, winner_ulid: null, updated_at: '2026-01-01T00:00:00Z',
  last_move_description: 'You played WINK', turn_expires_at: null, pending_invitation: null, is_public: false,
};

it('transforms a multiplayer game list item with a players array', () => {
  const item = transformGameListItem(baseListPayload);
  expect(item.players).toHaveLength(3);
  expect(item.maxPlayers).toBe(3);
  expect(item.myScore).toBe(10);
  expect(item.players[2].hasLeft).toBe(true);
  expect(item.players.find((p) => p.isMe)?.ulid).toBe('u1');
});

it('transforms game detail players with left state and turn order', () => {
  const game = transformGame({
    ulid: 'g1', language: 'en', status: 'active', max_players: 3,
    board: [], board_template: [],
    players: [
      { ulid: 'u1', username: 'You', avatar: null, avatar_color: null, score: 0, rack_count: 7, is_current_turn: true, turn_order: 1, has_left: false, left_reason: null },
      { ulid: 'u2', username: 'Tom', avatar: null, avatar_color: null, score: 0, rack_count: 0, is_current_turn: false, turn_order: 2, has_left: true, left_reason: 'removed' },
    ],
    my_rack: [], tiles_remaining: 50, current_turn_user_ulid: 'u1', winner_ulid: null,
    is_last_move: false, last_move: null, turn_expires_at: null, pending_invitation: null,
    is_public: false, can_join: false,
  } as any);
  expect(game.maxPlayers).toBe(3);
  expect(game.players[1].hasLeft).toBe(true);
  expect(game.players[1].leftReason).toBe('removed');
  expect(game.players[0].turnOrder).toBe(1);
});
```

- [ ] **Step 2: Run tests, confirm failure**

Run: `cd /Users/freek/dev/code/wordstockt-app && npx jest src/schemas/__tests__/game.schema.test.ts`
Expected: FAIL (types/fields missing).

- [ ] **Step 3: Update `src/types/game.ts`**

In `Player` add:
```ts
  turnOrder?: number;
  hasLeft?: boolean;
  leftReason?: string | null;
```
In `Game` add `maxPlayers: number;`.
Replace the `GameListItem.opponent` and `opponentScore` fields. Add a new interface and edit `GameListItem`:
```ts
export interface GameListPlayer {
  ulid: string;
  username: string;
  avatar: string | null;
  avatarColor: string | null;
  score: number;
  isCurrentTurn: boolean;
  isMe: boolean;
  hasLeft: boolean;
}

export interface GameListItem {
  ulid: string;
  language: string;
  status: GameStatus;
  maxPlayers: number;
  players: GameListPlayer[];
  myScore: number;
  isMyTurn: boolean;
  winnerUlid: string | null;
  updatedAt: string;
  lastMoveDescription: string | null;
  turnExpiresAt: string | null;
  pendingInvitation: GameListPendingInvitation | null;
  isPublic: boolean;
}
```
In `PendingGame` (defined in schema file) add `maxPlayers: number; playersJoined: number;` (also update the interface there).

- [ ] **Step 4: Update `src/schemas/game.schema.ts`**

`PlayerSchema`: add `turn_order: z.number().optional()`, `has_left: z.boolean().optional()`, `left_reason: z.string().nullish()`. `transformPlayer`: map `turnOrder: data.turn_order`, `hasLeft: data.has_left`, `leftReason: data.left_reason ?? null`.

`GameSchema`: add `max_players: z.number()`. `transformGame`: add `maxPlayers: data.max_players`.

Replace `GameListItemSchema` opponent/opponent_score with:
```ts
    max_players: z.number(),
    players: z.array(
      z.object({
        ulid: z.string(),
        username: z.string(),
        avatar: z.string().nullable(),
        avatar_color: z.string().nullish(),
        score: z.number(),
        is_current_turn: z.boolean(),
        is_me: z.boolean(),
        has_left: z.boolean(),
      }).passthrough()
    ),
    my_score: z.number(),
    is_my_turn: z.boolean(),
```
(remove `opponent`, `opponent_score`.)
Rewrite `transformGameListItem` to map `maxPlayers`, `players` (camelCase, `avatarColor: p.avatar_color ?? null`, `isCurrentTurn`, `isMe`, `hasLeft`), keep `myScore: data.my_score`, `isMyTurn: data.is_my_turn`. Remove `opponent`/`opponentScore`.

`PendingGameSchema`: add `max_players: z.number().optional().default(2)`, `players_joined: z.number().optional().default(1)`. `transformPendingGame`: add `maxPlayers`, `playersJoined`. Update the `PendingGame` interface.

- [ ] **Step 5: Run schema tests + typecheck**

Run: `npx jest src/schemas/__tests__/game.schema.test.ts && npx tsc --noEmit`
Expected: schema tests PASS. `tsc` will now report errors in `GameCard.tsx`, `useFilteredGames.ts`, `useRematch.ts` (they reference the removed `opponent`/`opponentScore`). That is EXPECTED — those are fixed in later tasks. Note the list of tsc errors; they should all be about `opponent`/`opponentScore`/`maxPlayers`. Do not fix them here.

- [ ] **Step 6: Format + commit**

```bash
npx prettier --write src/types/game.ts src/schemas/game.schema.ts src/schemas/__tests__/game.schema.test.ts
git add src/types/game.ts src/schemas/game.schema.ts src/schemas/__tests__/game.schema.test.ts
git commit -m "Types and schemas for N-player games"
```

---

## Task 2: CreateGameModal Players selector + create param

**Files:** `src/api/queries/useGames.ts`, `src/components/game-list/CreateGameModal.tsx`.

- [ ] **Step 1: Add `max_players` to CreateGameParams**

In `src/api/queries/useGames.ts`, the `CreateGameParams` interface — add `max_players?: number;`. Confirm the create mutation forwards the whole params object to `POST games` (it should already); if it cherry-picks fields, add `max_players`.

- [ ] **Step 2: Add the Players selector to the modal**

In `src/components/game-list/CreateGameModal.tsx`:
- Add local state `const [maxPlayers, setMaxPlayers] = useState(2);`.
- After the Language `SegmentedButtons`, add a `Players` label + `SegmentedButtons` with values `'2'|'3'|'4'` (buttons `[{value:'2',label:'2'},{value:'3',label:'3'},{value:'4',label:'4'}]`), `value={String(maxPlayers)}`, `onValueChange={(v) => setMaxPlayers(Number(v))}`.
- Add a small helper `Text` under it: "You'll invite players after creating the game." (style like the existing `label`/muted text).
- In `handleConfirm`, include `max_players: maxPlayers` in the `onConfirm({...})` payload.
- In `handleClose`, reset `setMaxPlayers(2)`.
- Update the `CreateGameParams` interface in this file (it has its own local `CreateGameParams`) to include `max_players?: number` so the `onConfirm` payload typechecks.

- [ ] **Step 3: Typecheck + test render**

Run: `npx tsc --noEmit` (the CreateGameModal-related code must be error-free; remaining errors should only be the Task 1 known ones in GameCard/hooks).
If there's an existing CreateGameModal test, run it; otherwise add a minimal render test asserting the Players control renders three options.

- [ ] **Step 4: Format + commit**

```bash
npx prettier --write src/api/queries/useGames.ts src/components/game-list/CreateGameModal.tsx
git add -A && git commit -m "Add player-count selector to create game"
```

---

## Task 3: useStartGame mutation + multi-invite confirm

**Files:** `src/api/queries/useGames.ts` (or `useInvitations.ts`).

- [ ] **Step 1: Add `useStartGame`**

Mirror an existing simple mutation (e.g. join/pass) in `useGames.ts`. Add:
```ts
export function useStartGame() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: async (gameUlid: string) => {
      const { data } = await apiClient.post(`/games/${gameUlid}/start`);
      return transformGame(GameSchema.parse(data.data ?? data));
    },
    onSuccess: (game) => {
      queryClient.invalidateQueries({ queryKey: gameKeys.detail(game.ulid) });
      queryClient.invalidateQueries({ queryKey: gameKeys.lists() });
    },
  });
}
```
Match the file's actual apiClient import, `gameKeys` helper, and response unwrapping (read a sibling mutation first; the start endpoint returns a `GameResource` — confirm whether it's wrapped in `{data:...}` like other endpoints).

- [ ] **Step 2: Confirm multi-invite**

Read `src/api/queries/useInvitations.ts`. The invite mutation posts a single `user_ulid` to `games/{ulid}/invite`. The backend now allows multiple distinct invites up to open seats, so no client change is needed beyond letting the user invite again. Confirm the invite hook can be called repeatedly (it should). If the UI hard-blocks a second invite, note it for Task 5/6.

- [ ] **Step 3: Typecheck + commit**

Run: `npx tsc --noEmit` (no NEW errors beyond Task 1's known ones).
```bash
npx prettier --write src/api/queries/useGames.ts
git add -A && git commit -m "Add useStartGame mutation"
```

---

## Task 4: ScoreBar — N-player equal-chip row

This is the largest UI task. Read the current `src/components/game/ScoreBar.tsx` fully first; preserve its animation/style language (avatars, score animation, turn highlight) while restructuring from a 2-player layout to an N-chip row.

**Files:** `src/components/game/ScoreBar.tsx` (+ any small subcomponents it already has).

- [ ] **Step 1: Restructure to a chip row over `game.players`**

Behavioral spec (match the approved mockups):
- Render one chip per `game.players`, ordered by `turnOrder` (fallback to array order). Each chip shows: avatar (initials/photo, player color), name ("You" for the self player — detect via `currentUserUlid`), score, the existing status dots (free-swap / blank markers as today), and the amber/red turn highlight when `player.isCurrentTurn`.
- The "tiles in bag" badge moves OUT of the center into the move/info row (left side), since the chip row now fills the top. Keep showing `game.tilesRemaining`.
- **Left players**: when `player.hasLeft`, render the chip dimmed (~55% opacity), greyed avatar, muted frozen score, and a small "LEFT" tag in place of the status dots; never show the turn highlight on them.
- **Pending seats (status 'pending')**: for a game that hasn't filled, render placeholder chips for the open seats. A seat with a pending invitation shows the invitee greyed with a "PENDING" tag (from `game.pendingInvitation`); an empty seat renders a dashed "Invite +" chip that triggers the invite action (open the existing invite modal / player picker). This generalizes today's single "Invite opponent +". Compute open seats from `game.maxPlayers - game.players.length - (pendingInvitation ? 1 : 0)`.
- **Start now**: when the game is pending, the current user is the creator (player with `turnOrder === 1` / first player), and `game.players.length >= 2`, show a "Start now" control in the info row that calls `useStartGame`. Disabled when fewer than 2 players.
- The layout is keyed to `game.maxPlayers` (the original roster), so it never collapses back to a 2-player layout when a player leaves.

Keep the component's existing prop interface as much as possible; it already receives the `game` and `currentUserUlid`. Wire the invite trigger to whatever the current ScoreBar uses for "Invite opponent" (find the existing handler/prop and reuse it for each empty seat).

- [ ] **Step 2: Typecheck + existing tests**

Run: `npx tsc --noEmit` then `npx jest src/components/game` (run any ScoreBar/ActionButtons tests). Fix failures.

- [ ] **Step 3: Add a render test**

Create `src/components/game/__tests__/ScoreBar.test.tsx` (mirror the existing `ActionButtons.test.tsx` setup) asserting: a 3-player active game renders three chips with the three names/scores; a game with a `hasLeft` player renders the "LEFT" tag and does not highlight that chip. Keep it lightweight (React Test Renderer / @testing-library/react-native as the repo uses).

- [ ] **Step 4: Format + commit**

```bash
npx prettier --write src/components/game/ScoreBar.tsx src/components/game/__tests__/ScoreBar.test.tsx
git add -A && git commit -m "Rebuild ScoreBar for 3-4 players with left-player and pending-seat states"
```

---

## Task 5: GameCard — multiplayer games-list display

**Files:** `src/components/game-list/GameCard.tsx` + test.

- [ ] **Step 1: Rebuild the card around `game.players`**

Read the current `GameCard.tsx` fully (it currently uses `game.opponent`, `game.opponentScore`, `game.myScore`, `game.isMyTurn`). New behavior (match the approved mockup):
- Compute `otherPlayers = game.players.filter((p) => !p.isMe)`. Header shows overlapping avatars of `otherPlayers` and their names comma-joined (ellipsis if long). For a pending game with no other players, keep the existing "Invite a player" / "Public game" empty states; for multiple pending invitees show "Waiting for N players…".
- Score box: render every player's score middot-joined with **yours bold** and **left players struck-through** (e.g. `214 · 289 · ~~176~~`). Use `game.players` ordered by the array order. Keep the "SCORES" label.
- Finished games: show placement ("1st of N") computed from sorted active scores (left players rank last), plus the existing Won/Lost badge driven by `game.winnerUlid === userUlid`.
- Pending + creator + ≥2 joined: show a "Start now" button (wire `useStartGame`) next to the existing Invite/Delete actions.
- Replace all `game.opponent`/`game.opponentScore` references. The accent/`isMyTurn` styling and `formatLastMove` helper stay.

- [ ] **Step 2: Typecheck + test**

Run: `npx tsc --noEmit` (the Task 1 known errors in GameCard must now be resolved). Add/adjust a `GameCard` render test asserting a 3-player card shows all three scores and the left player struck-through (or a testID marking it).

- [ ] **Step 3: Format + commit**

```bash
npx prettier --write src/components/game-list/GameCard.tsx
git add -A && git commit -m "Show all players on game list cards"
```

---

## Task 6: Hooks — useFilteredGames + useRematch

**Files:** `src/hooks/useFilteredGames.ts`, `src/hooks/useRematch.ts`.

- [ ] **Step 1: useFilteredGames**

Read it. Its bucketing (your-turn / their-turn / awaiting / finished) is driven by `isMyTurn` and `status`, which still exist — so logic is largely unchanged. Rename any `opponent`-centric local names to player-centric for clarity, and fix any reference to the removed `opponent` field. Keep behavior identical.

- [ ] **Step 2: useRematch**

Read it. It currently creates a game with `opponent_username` = the single opponent. Update it to recreate the game with the same roster: gather the other players' usernames from the finished game's players and pass `opponent_usernames` (array) and `max_players` to the create call. If the backend create only accepts `opponent_username` for a single rematch, adapt: for a 2-player game keep `opponent_username`; for 3-4, set `max_players` and invite the others after creation via the invite mutation. Prefer the simplest correct approach: set `max_players` to the original roster size and pass the other players for invitation. Confirm the create mutation/`CreateGameParams` supports what you call.

- [ ] **Step 3: Typecheck + tests + commit**

Run: `npx tsc --noEmit` (should now be fully clean across the repo). Run `npx jest` (whole suite) and fix any failures caused by the type changes.
```bash
npx prettier --write src/hooks/useFilteredGames.ts src/hooks/useRematch.ts
git add -A && git commit -m "Make game hooks player-centric and rematch roster-aware"
```

---

## Task 7: Full typecheck, test suite, lint

**Files:** none new — verification + any straggler fixes.

- [ ] **Step 1: Whole-repo typecheck**

Run: `npx tsc --noEmit`. Must be CLEAN (zero errors). Fix any remaining references to removed fields (`opponent`, `opponentScore`) anywhere in `src/` (grep for them).

- [ ] **Step 2: Jest**

Run: `npm test`. All tests pass (fix any that asserted the old single-opponent shape; adapt, don't delete).

- [ ] **Step 3: Lint + format**

Run: `npm run lint` and `npx prettier --check .` (or `--write` then re-check). Resolve issues introduced by this work.

- [ ] **Step 4: Commit any fixups**

```bash
git add -A && git commit -m "Finalize app multiplayer support: typecheck, tests, lint"
```

---

## Self-Review Notes (for the executor)

- **Order matters:** Task 1 intentionally leaves `tsc` errors in GameCard/hooks; they're resolved by Tasks 5-6. Don't "fix" them prematurely in Task 1.
- **Reuse existing pieces:** ScoreBar already has avatar, score-animation, turn-highlight, and an "Invite opponent" handler — reuse them; don't rebuild from scratch.
- **Spec coverage:** create-time player count (Task 2), invite-from-seat + Start now (Tasks 3-4), N-player score bar + left treatment (Task 4), list display (Task 5), rematch roster (Task 6), WebSocket needs no change (it refetches game state on `move.played`). 
- **Don't touch** the WebSocket hook — it already invalidates queries on events and is player-count agnostic.
- **Manual visual check** (optional, not blocking): the spec has approved mockups for the score bar, pending screen, list card, and create sheet.
