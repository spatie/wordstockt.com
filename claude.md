# WordStockt

A multiplayer word game (Wordfeud clone) built with Laravel backend and React Native frontend.

## Project Structure

- **Backend (this repo)**: Laravel 12 API running at https://wordstockt.com (via Valet)
- **Mobile App**: React Native + TypeScript at `../wordstockt-app`

## Tech Stack

### Backend
- PHP 8.2+ / Laravel 12
- Laravel Sanctum (API token auth)
- Laravel Reverb (WebSockets)
- SQLite (development)

### Mobile App
- React Native 0.76+
- TypeScript (strict mode)
- React Native Paper (Material Design 3)
- TanStack Query, Zustand, Zod

## Development

```bash
# Backend (API at https://wordstockt.com via Valet)
php artisan reverb:start   # WebSocket server

# Test users (seeded via UserSeeder)
freek@spatie.be / password
jessica@spatie.be / password
```

## App Commands

Run the interactive app helper:

```bash
php artisan app
```

This guides you through:
- **Run dev server** - Hot reload on simulator/browser (choose platform + API)
- **Build app** - EAS cloud build → download from expo.dev
- **Build + submit** - Build and upload to TestFlight
- **Push JS update** - Instant OTA update to installed apps (~30 sec)

### Workflow
1. **First time**: Build app → install via expo.dev or TestFlight
2. **JS changes**: Push JS update → restart app to get update
3. **Native changes**: Build app again → reinstall

## Coding Style

- **No transactions**: Don't wrap code in `DB::transaction()`
- **No else statements**: Prefer early returns over else blocks
- **No final classes**: Don't use `final` keyword on classes
- **Prefer collections**: Use Laravel collections over arrays/loops when it improves readability
- **Functions over comments**: Instead of comments, extract code into well-named functions
- **Actions pattern**: Business logic goes in `app/Actions/` with `Action` suffix and `execute()` method

## Testing Two-Player Games

**AI INSTRUCTION**: When testing multiplayer functionality, real-time updates, or game interactions between players, use this approach: one player in the browser (Playwright), the other player via API calls.

Since Playwright shares browser sessions across tabs, test two-player scenarios by:
- **Player 1**: Log in via browser (Playwright)
- **Player 2**: Make moves via API (curl)

### Setup

```bash
# 1. Start required services
php artisan reverb:start          # WebSocket server (port 8080)
php artisan queue:work --daemon   # Queue worker (required for broadcast events!)

# 2. Create API tokens for test users
php artisan tinker --execute="
\$freek = App\Models\User::where('email', 'freek@spatie.be')->first();
\$jessica = App\Models\User::where('email', 'jessica@spatie.be')->first();
echo 'FREEK_TOKEN=' . \$freek->createToken('test')->plainTextToken . PHP_EOL;
echo 'JESSICA_TOKEN=' . \$jessica->createToken('test')->plainTextToken . PHP_EOL;
"
```

### Create Game & Invite Player

```bash
# Create game as freek
curl -s -X POST "https://wordstockt.com.test/api/games" \
  -H "Authorization: Bearer $FREEK_TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"language": "nl"}'

# Invite jessica (get her ULID first)
curl -s -X POST "https://wordstockt.com.test/api/games/{gameUlid}/invite" \
  -H "Authorization: Bearer $FREEK_TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"user_ulid": "JESSICA_ULID"}'
```

### Make Moves via API

```bash
# Play a word (example: "LAN" on center, horizontally)
curl -s -X POST "https://wordstockt.com.test/api/games/{gameUlid}/moves" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"tiles":[
    {"letter":"L","points":3,"x":6,"y":7,"is_blank":false},
    {"letter":"A","points":1,"x":7,"y":7,"is_blank":false},
    {"letter":"N","points":1,"x":8,"y":7,"is_blank":false}
  ]}'

# Pass turn
curl -s -X POST "https://wordstockt.com.test/api/games/{gameUlid}/pass" \
  -H "Authorization: Bearer $TOKEN" -H "Accept: application/json"

# Swap tiles
curl -s -X POST "https://wordstockt.com.test/api/games/{gameUlid}/swap" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"tiles":[{"letter":"A","points":1}]}'
```

### Test Real-Time Updates

1. Log in as jessica in browser, navigate to game
2. Watch browser console for `[WS] Subscribed to private-game.{ulid}`
3. Make freek play a move via API
4. Verify browser console shows `[WS] Move played, refreshing game`
5. Verify UI updates with new board state, scores, turn indicator

### Check Word Validity

```bash
php artisan tinker --execute="
echo App\Models\Dictionary::where('word', 'lan')->where('language', 'nl')->exists() ? 'valid' : 'invalid';
"
```

### Test End Game Scenarios

**AI INSTRUCTION**: When the user asks to test end game functionality, game finishing, final scoring, winner determination, or consecutive passes ending a game, run the EndGameTestSeeder first to set up the scenario quickly.

Use the `EndGameTestSeeder` to create an almost-finished game:

```bash
php artisan db:seed --class=EndGameTestSeeder
```

This creates a game where:
- Board has several words already (HUIS, BEEN, WATER, LAND, KOE, ZEE)
- **Tile bag is EMPTY** (end game condition)
- Freek has 2 tiles (A, N), score: 127, it's his turn
- Jessica has 3 tiles (E, R, T), score: 118

Use this to test:
- Playing final tiles to end game
- End game from consecutive passes (pass twice each)
- Final score calculations and winner determination
- End game bonus (player who empties rack gets opponent's remaining tile points)

## iOS App Store Screenshots

**AI INSTRUCTION**: When the user asks to take App Store screenshots, ALWAYS use the LOCAL environment (wordstockt.com.test) with the local database. Use the `/screenshots` skill which automates the entire process including environment setup.

### Quick Command
```bash
# Run the screenshot skill to automate everything
# It will handle: seeding, simulator setup, certificate installation, login, and capturing all screens
```

### Manual Process (if needed)

**Prerequisites:**
1. **Install Valet certificate in simulator** (required for HTTPS to work):
   ```bash
   xcrun simctl keychain "iPhone 17 Pro Max" add-root-cert ~/.config/valet/CA/LaravelValetCASelfSigned.pem
   ```
2. **Seed database** with screenshot data:
   ```bash
   php artisan migrate:fresh --seed
   php artisan db:seed --class=ScreenshotGameSeeder
   php artisan db:seed --class=AppStoreScreenshotSeeder
   ```

**Login Credentials:**
- Email: `freek@spatie.be`
- Password: `password`

### Environment Setup (CRITICAL)

**ALWAYS use the local environment for screenshots.** The app determines which API to use based on the presence of `.env.local`:

| Environment File | API URL | Database |
|------------------|---------|----------|
| No `.env.local` | `https://wordstockt.com.test/api` | **Local** ✓ |
| `.env.local` with `EXPO_PUBLIC_USE_PRODUCTION=true` | `https://wordstockt.com/api` | Production ✗ |

**To ensure local environment:**
```bash
cd ../wordstockt-app

# Remove .env.local if it exists (forces local API)
rm -f .env.local

# Boot simulator
xcrun simctl boot "iPhone 17 Pro Max" 2>/dev/null || true

# Start app in dev mode (connects to wordstockt.com.test)
npx expo start --ios --clear
```

### Required Dimensions
App Store Connect requires exact dimensions:
- **1284 × 2778px** (iPhone 6.7" - iPhone 14/15/16 Pro Max)
- **1242 × 2688px** (iPhone 6.5" - iPhone 11 Pro Max, XS Max)

### Taking Screenshots

1. **Boot iPhone 17 Pro Max simulator**
2. **Install Valet certificate** (see Prerequisites above)
3. **Set clean status bar**:
   ```bash
   xcrun simctl status_bar "iPhone 17 Pro Max" override --time "9:41" --batteryState charged --batteryLevel 100 --cellularBars 4 --wifiMode active --wifiBars 3
   ```
4. **Use mobile-mcp tools** to navigate and capture:
   - `mobile_list_available_devices` - find simulator ID
   - `mobile_launch_app` - open the app
   - `mobile_click_on_screen_at_coordinates` - navigate
   - `mobile_save_screenshot` - save to file

5. **Resize to exact App Store dimensions**:
   ```bash
   cd public/screenshots/ios-appstore
   for f in *.png; do
     sips -z 2778 1284 "$f" --out "$f"
   done
   ```

### Screenshot Location
Screenshots are saved to: `public/screenshots/ios-appstore/`

### Recommended Screens
1. Game board with played words
2. Games list (Your Games)
3. Completed games
4. Public games
5. Friends list
6. Leaderboard
7. Profile/stats
8. Achievements

## Documentation

See `analysis/` folder for detailed docs:
- `architecture.md` - System overview
- `rules.md` - Game rules & PHP implementation
- `api.md` - API endpoints
- `status.md` - Feature status

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.5.2
- filament/filament (FILAMENT) - v4
- laravel/framework (LARAVEL) - v12
- laravel/horizon (HORIZON) - v5
- laravel/mcp (MCP) - v0
- laravel/prompts (PROMPTS) - v0
- laravel/reverb (REVERB) - v1
- laravel/sanctum (SANCTUM) - v4
- livewire/livewire (LIVEWIRE) - v3
- larastan/larastan (LARASTAN) - v3
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- pestphp/pest (PEST) - v4
- phpunit/phpunit (PHPUNIT) - v12
- rector/rector (RECTOR) - v2
- tailwindcss (TAILWINDCSS) - v4

## Skills Activation

This project has domain-specific skills available. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

- `pest-testing` — Tests applications using the Pest 4 PHP framework. Activates when writing tests, creating unit or feature tests, adding assertions, testing Livewire components, browser testing, debugging test failures, working with datasets or mocking; or when the user mentions test, spec, TDD, expects, assertion, coverage, or needs to verify functionality works.
- `tailwindcss-development` — Styles applications using Tailwind CSS v4 utilities. Activates when adding styles, restyling components, working with gradients, spacing, layout, flex, grid, responsive design, dark mode, colors, typography, or borders; or when the user mentions CSS, styling, classes, Tailwind, restyle, hero section, cards, buttons, or any visual/UI changes.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

- Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

## Artisan

- Use the `list-artisan-commands` tool when you need to call an Artisan command to double-check the available parameters.

## URLs

- Whenever you share a project URL with the user, you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain/IP, and port.

## Tinker / Debugging

- You should use the `tinker` tool when you need to execute PHP to debug code or query Eloquent models directly.
- Use the `database-query` tool when you only need to read from the database.

## Reading Browser Logs With the `browser-logs` Tool

- You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost.
- Only recent browser logs will be useful - ignore old logs.

## Searching Documentation (Critically Important)

- Boost comes with a powerful `search-docs` tool you should use before trying other approaches when working with Laravel or Laravel ecosystem packages. This tool automatically passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific documentation for the user's circumstance. You should pass an array of packages to filter on if you know you need docs for particular packages.
- Search the documentation before making code changes to ensure we are taking the correct approach.
- Use multiple, broad, simple, topic-based queries at once. For example: `['rate limiting', 'routing rate limiting', 'routing']`. The most relevant results will be returned first.
- Do not add package names to queries; package information is already shared. For example, use `test resource table`, not `filament 4 test resource table`.

### Available Search Syntax

1. Simple Word Searches with auto-stemming - query=authentication - finds 'authenticate' and 'auth'.
2. Multiple Words (AND Logic) - query=rate limit - finds knowledge containing both "rate" AND "limit".
3. Quoted Phrases (Exact Position) - query="infinite scroll" - words must be adjacent and in that order.
4. Mixed Queries - query=middleware "rate limit" - "middleware" AND exact phrase "rate limit".
5. Multiple Queries - queries=["authentication", "middleware"] - ANY of these terms.

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.

## Constructors

- Use PHP 8 constructor property promotion in `__construct()`.
    - <code-snippet>public function __construct(public GitHub $github) { }</code-snippet>
- Do not allow empty `__construct()` methods with zero parameters unless the constructor is private.

## Type Declarations

- Always use explicit return type declarations for methods and functions.
- Use appropriate PHP type hints for method parameters.

<code-snippet name="Explicit Return Types and Method Params" lang="php">
protected function isAccessible(User $user, ?string $path = null): bool
{
    ...
}
</code-snippet>

## Enums

- Typically, keys in an Enum should be TitleCase. For example: `FavoritePerson`, `BestLake`, `Monthly`.

## Comments

- Prefer PHPDoc blocks over inline comments. Never use comments within the code itself unless the logic is exceptionally complex.

## PHPDoc Blocks

- Add useful array shape type definitions when appropriate.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using the `list-artisan-commands` tool.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

## Database

- Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
- Use Eloquent models and relationships before suggesting raw database queries.
- Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM capabilities rather than bypassing them.
- Generate code that prevents N+1 query problems by using eager loading.
- Use Laravel's query builder for very complex database operations.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `list-artisan-commands` to check the available options to `php artisan make:model`.

### APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## Controllers & Validation

- Always create Form Request classes for validation rather than inline validation in controllers. Include both validation rules and custom error messages.
- Check sibling Form Requests to see if the application uses array or string based validation rules.

## Authentication & Authorization

- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.).

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Queues

- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

## Configuration

- Use environment variables only in configuration files - never use the `env()` function directly outside of config files. Always use `config('app.name')`, not `env('APP_NAME')`.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== laravel/v12 rules ===

# Laravel 12

- CRITICAL: ALWAYS use `search-docs` tool for version-specific Laravel documentation and updated code examples.
- Since Laravel 11, Laravel has a new streamlined file structure which this project uses.

## Laravel 12 Structure

- In Laravel 12, middleware are no longer registered in `app/Http/Kernel.php`.
- Middleware are configured declaratively in `bootstrap/app.php` using `Application::configure()->withMiddleware()`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- The `app\Console\Kernel.php` file no longer exists; use `bootstrap/app.php` or `routes/console.php` for console configuration.
- Console commands in `app/Console/Commands/` are automatically available and do not require manual registration.

## Database

- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 12 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models

- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.

=== pint/core rules ===

# Laravel Pint Code Formatter

- You must run `vendor/bin/pint --dirty` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test`, simply run `vendor/bin/pint` to fix any formatting issues.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `php artisan make:test --pest {name}`.
- Run tests: `php artisan test --compact` or filter: `php artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.
- CRITICAL: ALWAYS use `search-docs` tool for version-specific Pest documentation and updated code examples.
- IMPORTANT: Activate `pest-testing` every time you're working with a Pest or testing-related task.

=== tailwindcss/core rules ===

# Tailwind CSS

- Always use existing Tailwind conventions; check project patterns before adding new ones.
- IMPORTANT: Always use `search-docs` tool for version-specific Tailwind CSS documentation and updated code examples. Never rely on training data.
- IMPORTANT: Activate `tailwindcss-development` every time you're working with a Tailwind CSS or styling-related task.
</laravel-boost-guidelines>
