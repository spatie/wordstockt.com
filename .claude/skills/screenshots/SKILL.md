# App Store Screenshots Skill

Automates taking screenshots for iOS App Store and Google Play Store submissions.

## What this skill does

1. Seeds the LOCAL database with realistic game data (wordstockt.com.test)
2. Sets up the iOS simulator with proper device and status bar
3. Installs the Valet SSL certificate in the simulator
4. Launches the app and logs in (dismiss any "Save Password" dialogs)
5. Navigates through all required screens using ios-simulator-skill or mobile-mcp
6. **Verifies each screenshot** shows the correct content
7. Captures screenshots from iOS Simulator (never use browser/Playwright)
8. Resizes screenshots to exact App Store requirements

## Usage

This skill automates taking iOS App Store screenshots. Simply ask Claude to take screenshots:

```
"Take App Store screenshots"
"Update the iOS screenshots"
"Capture new screenshots for the App Store"
```

Claude will automatically:
- Seed the database with realistic data
- Set up the simulator and install the Valet certificate
- Launch and log into the app
- Navigate through all 8 screens and capture screenshots
- Resize them to App Store dimensions

## Prerequisites

- Valet installed and configured (for local API at wordstockt.com.test)
- Expo dev server not running (skill will start it)
- iOS Simulator installed (for iOS screenshots)
- Android Emulator installed (for Android screenshots, optional)

## What gets created

### iOS Screenshots
Location: `public/screenshots/ios-appstore/`

Files:
1. `01-game-board.png` - Active game with played words
2. `02-games-list.png` - Your Games tab showing active/pending games
3. `03-completed-games.png` - Completed games list
4. `04-public-games.png` - Public games available to join
5. `05-friends.png` - Friends list
6. `06-leaderboard.png` - Monthly/yearly leaderboards
7. `07-profile.png` - User profile with stats
8. `08-achievements.png` - Achievements screen

Dimensions: **1284 × 2778px** (iPhone 6.7" display)

### Android Screenshots
Location: `public/screenshots/android-playstore/`

Same 8 screens with Android-appropriate dimensions.

## Implementation Steps

### 1. Database Seeding

```bash
php artisan migrate:fresh --seed
php artisan db:seed --class=ScreenshotGameSeeder
php artisan db:seed --class=AppStoreScreenshotSeeder
```

This creates:
- Users: freek, jessica, and 8 others with stats
- Friendships between freek and 7 users
- 3 active games, including **one against jessica with best board state for screenshots**
- 7 completed games (freek won all)
- 1 pending invitation
- 4 public games from other users
- Realistic leaderboard data

**Important:** For game board screenshot, always use the game against jessica (seeded specifically for App Store presentation).

### 2. iOS Simulator Setup

```bash
# Boot iPhone 17 Pro Max (closest to 6.7" requirement)
SIMULATOR="iPhone 17 Pro Max"
xcrun simctl boot "$SIMULATOR" 2>/dev/null || echo "Already booted"
open -a Simulator

# Set clean status bar (9:41, full signal/battery)
xcrun simctl status_bar "$SIMULATOR" override \
  --time "9:41" \
  --batteryState charged \
  --batteryLevel 100 \
  --cellularBars 4 \
  --wifiMode active \
  --wifiBars 3

# Install Valet SSL certificate (CRITICAL - app won't connect without this)
xcrun simctl keychain "$SIMULATOR" add-root-cert ~/.config/valet/CA/LaravelValetCASelfSigned.pem
```

### 3. App Launch

```bash
# Start Expo dev server (from wordstockt-app directory)
cd ../wordstockt-app
npx expo start --ios &
EXPO_PID=$!

# Wait for Metro bundler to be ready
sleep 15

# App should auto-launch on simulator
# If not, launch manually:
# xcrun simctl launch "$SIMULATOR" com.wordstockt.app
```

### 4. Login

**Option A: Use ios-simulator-skill (semantic navigation, recommended):**
```
Invoke Skill tool with: skill="ios-simulator-skill", args="tap email field, type freek@spatie.be, tap password field, type password, tap login button"
```

**Option B: Use mobile-mcp tools (coordinate-based):**
```typescript
mobile_list_available_devices() // Get simulator ID
mobile_launch_app(device, "com.wordstockt.app")
mobile_click_on_screen_at_coordinates(device, 220, 180) // Email field
mobile_type_keys(device, "freek@spatie.be", false)
mobile_click_on_screen_at_coordinates(device, 220, 230) // Password field
mobile_type_keys(device, "password", false)
mobile_click_on_screen_at_coordinates(device, 220, 285) // Login button
```

**After login:** Check for "Save Password" dialog and dismiss it (tap "Not Now"). Wait 2 seconds before taking any screenshots.

### 5. Navigate and Capture

For each screen:
1. Navigate to the screen (ios-simulator-skill or mobile-mcp)
2. Wait for screen to load (2-3 seconds)
3. Take screenshot
4. **VERIFY** using Read tool - confirm correct screen, no dialogs

**CRITICAL:** For **01-game-board.png**, tap the game **against jessica** (not just first game). This game has the best board state for screenshots.

Example:
```typescript
// Navigate and capture
mobile_click_on_screen_at_coordinates(device, 220, 350) // Game against jessica
// Wait 2-3 seconds
mobile_save_screenshot(device, "public/screenshots/ios-appstore/01-game-board.png")
// Then READ the screenshot to verify it shows the game board
```

**Navigation targets:**
- **Games List**: Already on this screen after login
- **Game Board**: Tap game **against jessica** (220, 350)
- **Completed Games**: Tap "Completed" tab (330, 150)
- **Public Games**: Tap "Public" tab (220, 150)
- **Friends**: Tap "Friends" in bottom nav (110, 920)
- **Leaderboard**: Tap "Leaderboard" in bottom nav (220, 920)
- **Profile**: Tap "Profile" in bottom nav (330, 920)
- **Achievements**: From profile, tap achievements (220, 400)

**After EACH screenshot, verify:**
- Shows correct screen for filename
- No "Save Password" or system dialogs
- No loading spinners
- Clean status bar
- Retake if incorrect

### 6. Resize Screenshots

```bash
cd public/screenshots/ios-appstore
for f in *.png; do
  sips -z 2778 1284 "$f" --out "$f"
done
```

### 7. Cleanup

```bash
# Stop Expo dev server
kill $EXPO_PID

# Optionally shutdown simulator
# xcrun simctl shutdown "$SIMULATOR"
```

## Android Implementation

Similar process but with Android Emulator:

```bash
# List available emulators
emulator -list-avds

# Start emulator
emulator -avd Pixel_7_Pro_API_34 &

# Install and launch app
cd ../wordstockt-app
npx expo run:android

# Use mobile-mcp tools same as iOS
# Save to public/screenshots/android-playstore/
```

## Troubleshooting

### "Network error" on login
- **Cause**: Valet SSL certificate not trusted by simulator
- **Fix**: Run the certificate installation command (step 2)

### App shows Expo dev menu
- **Cause**: Not connected to Metro bundler
- **Fix**: Tap on `http://localhost:8081` in dev menu

### "No development build installed"
- **Cause**: Dev build not built yet
- **Fix**: Run `npx expo run:ios` from wordstockt-app directory (takes 5-10 min)

### Screenshots are wrong dimensions
- **Cause**: Not resized after capture
- **Fix**: Run the `sips` resize command (step 6)

### API returns 401/403
- **Cause**: User doesn't exist or wrong password
- **Fix**: Re-run database seeders (step 1)

## Manual Alternative

If automation fails, take screenshots manually:

1. Complete setup steps 1-3 above
2. Launch app on simulator
3. Login manually (freek@spatie.be / password)
4. Navigate to each screen manually
5. Use this command for each screen:
   ```bash
   xcrun simctl io "iPhone 17 Pro Max" screenshot public/screenshots/ios-appstore/01-game-board.png
   ```
6. Resize all at once (step 6)

## Related Documentation

- [CLAUDE.md](../../../CLAUDE.md) - iOS App Store Screenshots section
- [database/seeders/AppStoreScreenshotSeeder.php](../../../database/seeders/AppStoreScreenshotSeeder.php) - Seeds game data
- [database/seeders/ScreenshotGameSeeder.php](../../../database/seeders/ScreenshotGameSeeder.php) - Seeds specific game board