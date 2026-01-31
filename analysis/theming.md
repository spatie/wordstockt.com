# Theming Support for WordStockt Mobile App

## Current State

The app has a **well-organized, centralized theme** in `src/config/theme.ts`:
- Single dark theme based on `MD3DarkTheme` from React Native Paper
- ~50 color properties organized semantically (backgrounds, accents, tiles, multipliers, etc.)
- 54 components import colors directly from this file (296 color references)
- No light mode or theme switching capability
- `preferencesStore.ts` exists (Zustand + AsyncStorage) but only stores language preference

## Theme Design

**7 predefined themes** (user selects from curated list, no custom colors):

### Base Themes
| Theme | Background | Card/Board | Primary Accent | Empty Cell |
|-------|------------|------------|----------------|------------|
| Dark Navy (current) | `#0D1B2A` | `#1B2838` | `#4A90D9` | `#2C3E50` |
| Light | `#F5F7FA` | `#E8ECF0` | `#3498DB` | `#D5DBE1` |
| OLED Black | `#000000` | `#0A0A0A` | `#2563EB` | `#1A1A1A` |

### Accent Color Themes (dark variants)
| Theme | Background | Card/Board | Primary Accent | Empty Cell |
|-------|------------|------------|----------------|------------|
| Forest | `#0F1F1A` | `#1A2F28` | `#27AE60` | `#2D4A40` |
| Sunset | `#1F1410` | `#2D1F1A` | `#E74C3C` | `#3D2E28` |
| Royal | `#14101F` | `#1E1A2D` | `#9B59B6` | `#2D2840` |
| Ocean | `#0D1A1A` | `#152828` | `#1ABC9C` | `#1F3D3D` |

**Mockups**: See `public/theme-mockups.html` or https://wordstockt.com.test/theme-mockups.html

**Design rule**: Tiles and bonus squares (3W, 2W, 3L, 2L, star) use identical colors across all themes for gameplay consistency. Only backgrounds, UI elements, and accent colors change.

## Implementation Plan

### 1. Restructure Theme File

Update `src/config/theme.ts`:

```typescript
import { MD3DarkTheme, MD3LightTheme } from 'react-native-paper';

export type ThemeId = 'dark' | 'light' | 'oled' | 'forest' | 'sunset' | 'royal' | 'ocean';

// Shared colors - IDENTICAL across all themes for gameplay consistency
const sharedColors = {
  // Multiplier colors (board bonus squares)
  tripleWord: '#C0392B',
  doubleWord: '#E67E22',
  tripleLetter: '#1A5276',
  doubleLetter: '#3498DB',
  star: '#F39C12',

  // Tile colors (rack and placed tiles)
  tileClassicBackground: '#E8E4DC',
  tileText: '#1A1A1A',
  tileShadow: '#4A90D9',
  tileClassicSelected: '#D4E4F7',

  // Validation feedback
  validWord: '#2E7D32',
  invalidWord: '#C62828',
  placementError: '#E65100',

  // Game result
  gameWon: '#27AE60',
  gameLost: '#7F8C8D',
  warning: '#FF9800',
};

// Theme color definitions
const themeColors = {
  dark: { background: '#0D1B2A', backgroundLight: '#1B2838', primary: '#4A90D9', cellBackground: '#2C3E50', textPrimary: '#FFFFFF', textSecondary: '#8B9DC3' },
  light: { background: '#F5F7FA', backgroundLight: '#E8ECF0', primary: '#3498DB', cellBackground: '#D5DBE1', textPrimary: '#1A1A1A', textSecondary: '#666666' },
  oled: { background: '#000000', backgroundLight: '#0A0A0A', primary: '#2563EB', cellBackground: '#1A1A1A', textPrimary: '#FFFFFF', textSecondary: '#888888' },
  forest: { background: '#0F1F1A', backgroundLight: '#1A2F28', primary: '#27AE60', cellBackground: '#2D4A40', textPrimary: '#FFFFFF', textSecondary: '#8BAA9D' },
  sunset: { background: '#1F1410', backgroundLight: '#2D1F1A', primary: '#E74C3C', cellBackground: '#3D2E28', textPrimary: '#FFFFFF', textSecondary: '#C4A07A' },
  royal: { background: '#14101F', backgroundLight: '#1E1A2D', primary: '#9B59B6', cellBackground: '#2D2840', textPrimary: '#FFFFFF', textSecondary: '#A89EC4' },
  ocean: { background: '#0D1A1A', backgroundLight: '#152828', primary: '#1ABC9C', cellBackground: '#1F3D3D', textPrimary: '#FFFFFF', textSecondary: '#7FBAAA' },
};

// Build full theme objects
export const themes = Object.fromEntries(
  Object.entries(themeColors).map(([id, colors]) => {
    const base = id === 'light' ? MD3LightTheme : MD3DarkTheme;
    return [id, {
      ...base,
      colors: { ...base.colors, ...sharedColors, ...colors },
    }];
  })
) as Record<ThemeId, typeof MD3DarkTheme>;

// For backward compatibility during migration
export const colors = themes.dark.colors;
```

### 2. Backend: Add Theme to User Model

**Migration** - Add `theme` column to users table:
```php
Schema::table('users', function (Blueprint $table) {
    $table->string('theme')->default('dark');
});
```

**User Model** - Add to `$fillable` and `$casts`:
```php
protected $fillable = [..., 'theme'];
```

**API** - Include theme in user response and add update endpoint:
```php
// routes/api.php
Route::patch('/user/theme', [UserController::class, 'updateTheme']);

// UserController
public function updateTheme(Request $request): JsonResponse
{
    $request->validate(['theme' => 'required|in:dark,light,oled,forest,sunset,royal,ocean']);
    $request->user()->update(['theme' => $request->theme]);
    return response()->json(['theme' => $request->theme]);
}
```

### 3. Mobile: Add Theme to Auth Store

Update `src/stores/authStore.ts` to include theme from user data:

```typescript
interface User {
  // ... existing fields
  theme: ThemeId;
}

// When user logs in, theme comes from API response
// When theme changes, call API to persist
```

Update `src/stores/preferencesStore.ts` (or keep local fallback for logged-out state):

```typescript
import { ThemeId } from '@/config/theme';

interface PreferencesState {
  preferredLanguage: 'nl' | 'en';
  theme: ThemeId;  // Local fallback
  setTheme: (theme: ThemeId) => void;
}
```

### 4. Update Root Layout

Update `app/_layout.tsx`:

```typescript
import { themes, ThemeId } from '@/config/theme';

export default function RootLayout() {
  const themeId = usePreferencesStore(s => s.theme);
  const theme = themes[themeId] ?? themes.dark;

  return (
    <PaperProvider theme={theme}>
      {/* ... */}
    </PaperProvider>
  );
}
```

### 5. Migrate Components

Replace direct color imports with `useTheme()`:

```typescript
// Before (54 components)
import { colors } from '@/config/theme';
<View style={{ backgroundColor: colors.background }} />

// After
import { useTheme } from 'react-native-paper';
const { colors } = useTheme();
<View style={{ backgroundColor: colors.background }} />
```

### 6. Add Theme Picker UI

Create theme selector (likely in Profile/Settings):
- Grid of theme preview swatches
- Tap to select, instant preview
- Persist to AsyncStorage via preferences store

## Files to Modify

### Backend (Laravel)
| File | Changes |
|------|---------|
| `database/migrations/xxx_add_theme_to_users.php` | Add `theme` column |
| `app/Models/User.php` | Add `theme` to fillable |
| `app/Http/Controllers/Api/UserController.php` | Add `updateTheme()` method |
| `app/Http/Resources/UserResource.php` | Include `theme` in response |
| `routes/api.php` | Add `PATCH /user/theme` route |

### Mobile App (React Native)
| File | Changes |
|------|---------|
| `src/config/theme.ts` | Define all 7 themes, export `themes` object |
| `src/stores/authStore.ts` | Include theme from user, add `setTheme` action |
| `src/api/user.ts` | Add `updateTheme()` API call |
| `app/_layout.tsx` | Dynamic theme from auth store |
| 54 component files | Replace `colors` import with `useTheme()` |
| Profile/Settings screen | Add theme picker UI |

## Verification

1. Switch between all 7 themes in the app
2. Verify game board renders correctly (multipliers, tiles visible)
3. Check text contrast in all themes
4. Verify theme persists across app restarts (local storage)
5. Verify theme syncs to backend (check database)
6. Log in on another device, verify theme syncs from backend
7. Test on both iOS and Android
