# App Icon Generation

## Source File

The master icon is defined in HTML at `app-icon.html`. This renders a 1024x1024px board grid with colored multiplier squares.

## Regenerating Icons

1. Open the icon in browser and take a screenshot at 1024x1024:

```bash
# Using Chrome DevTools MCP, navigate to the icon page
# Resize viewport to 1024x1024
# Take screenshot and save to logos/icon-1024.png
```

2. Generate all sizes using sips:

```bash
cd public/assets/logos

# iOS sizes
sips -z 180 180 icon-1024.png --out ios-180.png
sips -z 120 120 icon-1024.png --out ios-120.png
sips -z 87 87 icon-1024.png --out ios-87.png
sips -z 80 80 icon-1024.png --out ios-80.png
sips -z 60 60 icon-1024.png --out ios-60.png
sips -z 58 58 icon-1024.png --out ios-58.png
sips -z 40 40 icon-1024.png --out ios-40.png

# Android sizes
sips -z 512 512 icon-1024.png --out android-512.png
sips -z 192 192 icon-1024.png --out android-192.png
sips -z 144 144 icon-1024.png --out android-144.png
sips -z 96 96 icon-1024.png --out android-96.png
sips -z 72 72 icon-1024.png --out android-72.png
sips -z 48 48 icon-1024.png --out android-48.png
sips -z 36 36 icon-1024.png --out android-36.png
```

## Icon Sizes Reference

### iOS
| File | Size | Purpose |
|------|------|---------|
| `icon-1024.png` | 1024x1024 | App Store |
| `ios-180.png` | 180x180 | iPhone @3x |
| `ios-120.png` | 120x120 | iPhone @2x |
| `ios-87.png` | 87x87 | Settings @3x |
| `ios-80.png` | 80x80 | Spotlight @2x |
| `ios-60.png` | 60x60 | iPhone @2x |
| `ios-58.png` | 58x58 | Settings @2x |
| `ios-40.png` | 40x40 | Spotlight @2x |

### Android
| File | Size | Purpose |
|------|------|---------|
| `android-512.png` | 512x512 | Play Store |
| `android-192.png` | 192x192 | xxxhdpi |
| `android-144.png` | 144x144 | xxhdpi |
| `android-96.png` | 96x96 | xhdpi |
| `android-72.png` | 72x72 | hdpi |
| `android-48.png` | 48x48 | mdpi |
| `android-36.png` | 36x36 | ldpi |

---

# Splash Screen Generation

## Source Files

Splash screens are defined in HTML:
- `splash-phone.html` - Phone layout (portrait, responsive)
- `splash-tablet.html` - Tablet layout (landscape-optimized)

Both feature the radial glow effect with the board icon and tile-based wordmark.

## Design Elements

- **Background**: Radial gradient from `#2C3E50` (center) to `#1B2838` (edges)
- **Board icon**: 5x5 grid with colored multiplier squares and subtle glow shadows
- **Wordmark**: "WORDSTOCKT" as individual tiles
  - Light tiles: `#E8E4DC` with `#2C3E50` text
  - Blue tiles (W, S): `#5B8FB9` with white text

## Regenerating Splash Screens

### Using Chrome DevTools MCP

1. **Generate the main splash icon (1024x1024)**:
```
Navigate to: https://wordstockt.com.test/assets/splash-phone.html
Resize page to: 1024 x 1024
Take screenshot, save to: splash/splash-1024.png
```

2. **Generate high-res version (2048x2048)**:
```
Resize page to: 2048 x 2048
Take screenshot, save to: splash/splash-2048.png
```

3. **Generate phone-specific (iPhone Pro Max)**:
```
Resize page to: 1284 x 2778
Take screenshot, save to: splash/splash-phone-1284x2778.png
```

4. **Generate tablet portrait (iPad Pro 12.9")**:
```
Navigate to: https://wordstockt.com.test/assets/splash-tablet.html
Resize page to: 2048 x 2732
Take screenshot, save to: splash/splash-tablet-2048x2732.png
```

5. **Generate tablet landscape**:
```
Resize page to: 2732 x 2048
Take screenshot, save to: splash/splash-tablet-2732x2048.png
```

### Copy to App

```bash
# Copy main splash icon to React Native app
cp public/assets/splash/splash-1024.png ../wordstockt-app/assets/splash-icon.png
```

## Splash Screen Size Reference

### Expo/React Native (Primary)
Expo uses a single image and resizes automatically:
| File | Size | Notes |
|------|------|-------|
| `splash-icon.png` | 1024x1024 | Main splash icon (PNG, transparent OK) |

Configure in `app.json`:
```json
{
  "splash": {
    "image": "./assets/splash-icon.png",
    "resizeMode": "contain",
    "backgroundColor": "#1B2838"
  }
}
```

### iOS Device Sizes (for reference)
| Device | Portrait | Landscape |
|--------|----------|-----------|
| iPhone SE/8 | 750 × 1334 | 1334 × 750 |
| iPhone 12/13/14 | 1170 × 2532 | 2532 × 1170 |
| iPhone 14/15/16 Pro Max | 1290 × 2796 | 2796 × 1290 |
| iPad Pro 11" | 1668 × 2388 | 2388 × 1668 |
| iPad Pro 12.9" | 2048 × 2732 | 2732 × 2048 |

### Android Density Buckets (for reference)
| Density | Portrait | Landscape |
|---------|----------|-----------|
| MDPI (1x) | 320 × 480 | 480 × 320 |
| HDPI (1.5x) | 480 × 800 | 800 × 480 |
| XHDPI (2x) | 720 × 1280 | 1280 × 720 |
| XXHDPI (3x) | 1080 × 1920 | 1920 × 1080 |
| XXXHDPI (4x) | 1440 × 2560 | 2560 × 1440 |

### Android 12+ Splash Screen API
Android 12+ uses a circular mask for splash icons:
- Icon with background: 240×240 dp (fits in 160dp circle)
- Icon without background: 288×288 dp (fits in 192dp circle)
- Keep content in inner 66% to avoid clipping

---

# Colors Used

From the app theme (`wordstockt-app/src/config/theme.ts`):

- Background: `#1B2838`
- Empty cell: `#2C3E50`
- Triple Word (red): `#C0392B`
- Double Word (orange): `#E67E22`
- Triple Letter (navy): `#1A5276`
- Double Letter (blue): `#3498DB`
- Center star (gold): `#F39C12`

Wordmark tiles:
- Light tile background: `#E8E4DC`
- Light tile text: `#2C3E50`
- Blue tile background: `#5B8FB9`
- Blue tile text: `#FFFFFF`
