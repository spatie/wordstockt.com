#!/usr/bin/env bash
#
# Launch N iOS simulators, each running the WordStockt dev build connected to
# Metro and auto-logged-in as a different test user (user1, user2, ...), plus a
# ready N-player game among them. No manual typing/tapping required.
#
# Why this is non-obvious:
#   1. The installed dev build won't auto-connect to Metro; `expo run:ios
#      --device <udid>` launches it connected without the "Open in app?" dialog.
#   2. Login is bypassed by pre-seeding the Zustand auth store. The app reads
#      auth from the AsyncStorage key **auth-storage** (NOT "l") at:
#      <data-container>/Library/Application Support/<bundle>/RCTAsyncLocalStorage_V1/manifest.json
#      The value is {"state":{"token":..,"user":{..}},"version":0}.
#
# Usage:
#   COUNT=3 DEVICES="<udid1> <udid2> <udid3>" ./seed-and-launch.sh
#   (DEVICES optional; defaults to three distinct booted-capable iPhones.)
#
set -euo pipefail

SKILL_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
BACKEND_DIR="${BACKEND_DIR:-$(cd "$SKILL_DIR/../../.." && pwd)}"
# The app is usually a sibling of the backend, but in a conductor workspace the
# backend lives under conductor/ while the app stays in the dev checkout.
if [ -z "${APP_DIR:-}" ]; then
  if [ -d "$BACKEND_DIR/../wordstockt-app" ]; then
    APP_DIR="$BACKEND_DIR/../wordstockt-app"
  else
    APP_DIR="$HOME/dev/code/wordstockt-app"
  fi
fi
BUNDLE_ID="${BUNDLE_ID:-com.wordstockt.app}"
COUNT="${COUNT:-3}"
LANG_CODE="${LANG_CODE:-nl}"

# Resolve device UDIDs: use $DEVICES if given, else pick distinct iPhone models.
if [ -n "${DEVICES:-}" ]; then
  read -r -a UDIDS <<< "$DEVICES"
else
  mapfile -t UDIDS < <(
    xcrun simctl list devices available \
      | grep -i "iPhone" \
      | grep -oE "[0-9A-F-]{36}" \
      | head -n "$COUNT"
  )
fi
[ "${#UDIDS[@]}" -ge "$COUNT" ] || { echo "Need $COUNT iPhone simulators; found ${#UDIDS[@]}. Set DEVICES=..." >&2; exit 1; }

echo "==> Backend: $BACKEND_DIR"
echo "==> App:     $APP_DIR"
echo "==> Devices: ${UDIDS[*]:0:$COUNT}"

# 1. Ensure users user1..userN exist (password == username) and a ready game.
cat > /tmp/wsk_seed.php <<PHP
<?php
use App\Domain\User\Models\User;
use App\Domain\Game\Actions\CreateGameAction;
use App\Domain\Game\Actions\JoinGameAction;
use Illuminate\Support\Facades\Hash;
\$users = [];
for (\$n = 1; \$n <= $COUNT; \$n++) {
    \$u = User::firstWhere('email', "user{\$n}@spatie.be") ?? User::factory()->create(['email' => "user{\$n}@spatie.be"]);
    \$u->update(['username' => "user{\$n}", 'password' => Hash::make("user{\$n}"), 'email_verified_at' => now()]);
    \$users[\$n] = \$u->fresh();
}
\$game = app(CreateGameAction::class)->execute(\$users[1], '$LANG_CODE', null, 'standard', null, false, $COUNT);
for (\$n = 2; \$n <= $COUNT; \$n++) { app(JoinGameAction::class)->execute(\$game->fresh(), \$users[\$n]); }
echo "GAME=" . \$game->fresh()->ulid . PHP_EOL;
foreach (\$users as \$n => \$u) {
    \$token = \$u->createToken('simtest')->plainTextToken;
    \$blob = json_encode(['state' => ['token' => \$token, 'user' => [
        'ulid' => \$u->ulid, 'username' => \$u->username, 'email' => \$u->email,
        'avatar' => \$u->avatar, 'avatarColor' => \$u->avatar_color, 'eloRating' => \$u->elo_rating,
        'gamesPlayed' => \$u->games_played, 'gamesWon' => \$u->games_won, 'isGuest' => false,
        'emailVerifiedAt' => optional(\$u->email_verified_at)->toISOString(),
        'createdAt' => optional(\$u->created_at)->toISOString(),
    ]], 'version' => 0]);
    file_put_contents("/tmp/wsk_auth{\$n}.json", \$blob);
}
PHP
( cd "$BACKEND_DIR" && php artisan tinker --execute="require '/tmp/wsk_seed.php';" | grep -i "^GAME=" )

# 2. Boot devices + build/launch the dev build connected to Metro (first run
#    starts Metro and compiles; later devices reuse the build and bundler).
for i in $(seq 0 $((COUNT-1))); do
  xcrun simctl boot "${UDIDS[$i]}" 2>/dev/null || true
done
open -a Simulator || true
for i in $(seq 0 $((COUNT-1))); do
  echo "==> expo run:ios on ${UDIDS[$i]} (this connects it to Metro)"
  ( cd "$APP_DIR" && npx expo run:ios --device "${UDIDS[$i]}" >/tmp/wsk_run_$i.log 2>&1 )
done

# 3. Pre-seed auth-storage per device, then relaunch so the app rehydrates logged in.
for i in $(seq 0 $((COUNT-1))); do
  n=$((i+1)); udid="${UDIDS[$i]}"
  xcrun simctl terminate "$udid" "$BUNDLE_ID" 2>/dev/null || true
  dc=$(xcrun simctl get_app_container "$udid" "$BUNDLE_ID" data)
  dir="$dc/Library/Application Support/$BUNDLE_ID/RCTAsyncLocalStorage_V1"
  mkdir -p "$dir"; m="$dir/manifest.json"; [ -f "$m" ] || echo '{}' > "$m"
  python3 - "$m" "/tmp/wsk_auth$n.json" <<'PY'
import json, sys
m_path, auth = sys.argv[1], sys.argv[2]
try: m = json.load(open(m_path))
except Exception: m = {}
m['auth-storage'] = open(auth).read()
m.pop('l', None)
json.dump(m, open(m_path, 'w'))
PY
  xcrun simctl launch "$udid" "$BUNDLE_ID" >/dev/null 2>&1 || true
  echo "==> user$n logged in on $udid"
done

echo "Done. Each simulator is logged in as userN (password userN) in a shared $COUNT-player game."
