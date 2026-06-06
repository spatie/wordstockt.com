---
name: multiplayer-simulators
description: Use when you need to test WordStockt multiplayer (3-4 player) on several iOS simulators at once, each logged in as a different user, without manually typing logins or tapping the Metro connect dialog.
---

# Multiplayer iOS Simulators (auto-login)

## Overview

Spins up N iOS simulators, each running the WordStockt dev build connected to Metro and **auto-logged-in as a different user** (user1, user2, …), plus a ready N-player game among them. Lets you drive a 3-/4-player game across separate devices for real-time/turn/removal/endgame testing.

This exists because the simulator UI can't be typed/tapped programmatically in this environment (idb is broken), so login and Metro-connect are done without touching the screen.

## When to use

- Manually testing 3-4 player games, turns, real-time updates, player removal, or endgame across multiple devices.
- You want each simulator on a distinct account with one command.

For a single quick check, one simulator + API calls (see project `CLAUDE.md`) or the web build (`npx expo start --web`, log in via browser) is lighter.

## Run it

```bash
.claude/skills/multiplayer-simulators/seed-and-launch.sh          # 3 players
COUNT=4 .claude/skills/multiplayer-simulators/seed-and-launch.sh  # 4 players
DEVICES="<udid1> <udid2> <udid3>" .claude/skills/multiplayer-simulators/seed-and-launch.sh
```

Credentials it creates: `userN@spatie.be` / `userN` (password = username). First device = user1, etc.

Prereqs: Valet serving the local backend (`valet link && valet secure wordstockt.com`), no `.env.local` in the app (forces local API), and ideally Reverb + a queue worker running for live updates (`php artisan reverb:start --host=127.0.0.1` and `php artisan queue:work`).

## The two non-obvious tricks

1. **Connect to Metro without the dialog.** A freshly installed dev build shows "No development servers found" and `simctl openurl` triggers an "Open in WordStockt?" confirm you can't tap. `npx expo run:ios --device <udid>` builds/installs/launches it already connected. First device compiles + starts Metro; later devices reuse both.

2. **Skip login by pre-seeding auth.** The app's auth is a Zustand `persist` store read from AsyncStorage key **`auth-storage`** (the legacy key `l` is ignored). On the simulator that file lives at:
   `<data-container>/Library/Application Support/<bundle>/RCTAsyncLocalStorage_V1/manifest.json`
   Get the container with `xcrun simctl get_app_container <udid> com.wordstockt.app data`. Write the key as a JSON **string**:
   `{"state":{"token":"<sanctum>","user":{ulid,username,email,avatar,avatarColor,eloRating,gamesPlayed,gamesWon,isGuest,emailVerifiedAt,createdAt}},"version":0}`
   Terminate the app, write the manifest, then `simctl launch` so it rehydrates logged in.

## Common mistakes

- Writing the token under key `l` (what an old session happened to use) — the app reads `auth-storage`; it will stay on the login screen.
- Writing the manifest while the app is running — it gets clobbered on persist. Terminate first, write, then launch.
- Two booted simulators with the same device name — `simctl launch "iPhone 17 Pro Max"` is ambiguous; always target by UDID.
- App pointing at production — remove `wordstockt-app/.env.local` before the build so Metro bundles the local API.
- `user`-object shape must match the app's camelCase `User` type, or the UI shows blanks; the script mirrors the exact fields.

## Teardown

Stop Metro/Reverb/queue, `valet unsecure wordstockt.com && valet unlink wordstockt.com`, restore `wordstockt-app/.env.local`.
