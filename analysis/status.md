# WordStockt Project Status

## Completed Features

### Backend (Laravel) ✅

#### Authentication
- [x] User registration
- [x] User login with Sanctum tokens
- [x] User logout
- [x] Get current user profile
- [x] Update user profile

#### Game Management
- [x] Create game (with or without opponent)
- [x] Join pending game
- [x] List user's games
- [x] List pending games
- [x] Get game state

#### Game Logic
- [x] Play move (place tiles)
- [x] Pass turn
- [x] Swap tiles
- [x] Resign from game
- [x] Turn switching
- [x] Game end detection

#### Validation
- [x] Tile placement validation (line, gaps, bounds)
- [x] First move must cover center
- [x] Must connect to existing tiles
- [x] Word dictionary validation
- [x] Tiles-in-rack validation

#### Scoring
- [x] Letter point values (Dutch & English)
- [x] Letter multipliers (DL, TL)
- [x] Word multipliers (DW, TW)
- [x] Bingo bonus (+50 for 7 tiles)
- [x] End game penalties

#### Dictionary
- [x] Dictionary import command
- [x] Dutch word list (OpenTaal)
- [x] English word list
- [x] Word validation service

#### Real-time
- [x] WebSocket server (Reverb)
- [x] MovePlayed event broadcast
- [x] MessageSent event broadcast
- [x] GameInvitation event

#### Chat
- [x] Get game messages
- [x] Send message
- [x] Message broadcast

#### Users
- [x] Search users
- [x] Get user profile
- [x] Leaderboard (top 50)
- [x] Games played/won stats

---

### Frontend (React Native) - To Build

#### Project Setup
- [ ] Initialize React Native project (Expo or bare)
- [ ] Configure TypeScript (strict mode)
- [ ] Install dependencies (TanStack Query, Zustand, Zod, Axios)
- [ ] Install React Native Paper + theme setup
- [ ] Set up React Navigation

#### Types & Schemas
- [ ] Define TypeScript types (`src/types/`)
- [ ] Create Zod schemas (`src/schemas/`)
- [ ] API response transformers (snake_case → camelCase)

#### API Layer
- [ ] Axios client with interceptors
- [ ] TanStack Query hooks for games
- [ ] TanStack Query hooks for auth
- [ ] Error handling utilities

#### State Management
- [ ] Auth store (Zustand + persist)
- [ ] Game UI store (pending tiles)

#### Screens
- [ ] Login screen
- [ ] Register screen
- [ ] Home screen (game list)
- [ ] Game screen

#### Game Components
- [ ] GameBoard (15x15 grid)
- [ ] BoardCell (with multiplier colors)
- [ ] Tile component
- [ ] TileRack
- [ ] ScoreBar
- [ ] ActionButtons (Recall, Pass, Play)

#### Core Features
- [ ] Tile placement (tap to select, tap to place)
- [ ] Remove pending tiles
- [ ] Recall all tiles
- [ ] Submit move
- [ ] Pass turn
- [ ] Error display (Alert)

---

## Feature Roadmap

### Phase 1: Core Game (MVP)
- [ ] Project setup & navigation
- [ ] Authentication (login/register/logout)
- [ ] Game list screen
- [ ] Game board display
- [ ] Tile placement
- [ ] Submit moves
- [ ] Pass turn
- [ ] Error handling

### Phase 2: Polish
- [ ] Blank tile letter selection
- [ ] Swap tiles feature
- [ ] Score animations
- [ ] Turn notifications (local)

### Phase 3: Real-time
- [ ] WebSocket connection
- [ ] Live opponent moves
- [ ] Chat feature
- [ ] Push notifications

### Phase 4: Social
- [ ] User search
- [ ] Game invitations
- [ ] Friend list
- [ ] Leaderboard

---

## Known Issues

### Backend
1. **Single-tile words**: Backend allows single-tile plays that don't form words (edge case)

---

## Technical Debt

### Backend
1. **Tests**: Need unit tests for services
2. **API Documentation**: OpenAPI/Swagger spec
3. **Error codes**: Standardize error response format
4. **Logging**: Add structured logging
5. **Rate limiting**: Add API rate limits

### Frontend
1. **Tests**: Jest + React Native Testing Library
2. **E2E Tests**: Detox or Maestro
3. **Error boundaries**: React error boundaries
4. **Offline support**: Queue moves when offline

---

## Development Notes

### Running Locally

**Backend:**
```bash
cd wordstockt.com
php artisan serve          # API server (http://localhost:8000)
php artisan reverb:start   # WebSocket server (ws://localhost:8080)
```

**Frontend (React Native):**
```bash
cd mobile

# With Expo
npx expo start

# Or bare React Native
npx react-native run-ios
npx react-native run-android
```

### Test Users
```
Email: freek@test.com
Password: password

Email: jessica@test.com
Password: password
```

### Useful Commands
```bash
# Reset database
php artisan migrate:fresh

# Import dictionary
php artisan dictionary:import nl

# Create user in tinker
php artisan tinker
> User::create(['username' => 'test', 'email' => 'test@test.com', 'password' => Hash::make('password')])
```

### Recommended VS Code Extensions
- ESLint
- Prettier
- TypeScript + JavaScript
- React Native Tools
- Zod Schemas
