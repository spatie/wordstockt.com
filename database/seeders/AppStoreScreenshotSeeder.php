<?php

namespace Database\Seeders;

use App\Domain\Game\Enums\GameStatus;
use App\Domain\Game\Enums\MoveType;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\GamePlayer;
use App\Domain\Game\Models\Move;
use App\Domain\Game\Support\Board;
use App\Domain\Game\Support\TileBag;
use App\Domain\User\Models\Friend;
use App\Domain\User\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AppStoreScreenshotSeeder extends Seeder
{
    private array $users = [];

    public function run(): void
    {
        $this->createUsers();
        $this->createFriendships();
        $this->createActiveGames();
        $this->createCompletedGames();
        $this->createPendingGames();
        $this->createPublicGames();

        $this->command->info('App Store screenshot data seeded successfully!');
    }

    private function createUsers(): void
    {
        // Main user (freek) should already exist
        $this->users['freek'] = User::where('email', 'freek@spatie.be')->first();
        $this->users['jessica'] = User::where('email', 'jessica@spatie.be')->first();

        // Create additional users for a fuller experience
        $additionalUsers = [
            ['username' => 'emmaw', 'email' => 'emma@example.com'],
            ['username' => 'jameschen', 'email' => 'james@example.com'],
            ['username' => 'sophiem', 'email' => 'sophie@example.com'],
            ['username' => 'oliverb', 'email' => 'oliver@example.com'],
            ['username' => 'avaj', 'email' => 'ava@example.com'],
            ['username' => 'liamd', 'email' => 'liam@example.com'],
            ['username' => 'miag', 'email' => 'mia@example.com'],
            ['username' => 'noahm', 'email' => 'noah@example.com'],
        ];

        foreach ($additionalUsers as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'username' => $userData['username'],
                    'email' => $userData['email'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                    'games_won' => rand(5, 50),
                    'games_played' => rand(20, 100),
                    'elo_rating' => rand(1000, 1800),
                ]
            );
            $this->users[$userData['username']] = $user;
        }

        // Update freek's stats for leaderboard
        if ($this->users['freek']) {
            $this->users['freek']->update([
                'games_won' => 47,
                'games_played' => 89,
                'elo_rating' => 1650,
            ]);
        }

        $this->command->info('Created '.count($this->users).' users');
    }

    private function createFriendships(): void
    {
        $freek = $this->users['freek'];

        if (! $freek) {
            return;
        }

        // Add 7 friends for freek
        $friends = ['jessica', 'emmaw', 'jameschen', 'sophiem', 'oliverb', 'avaj', 'liamd'];

        foreach ($friends as $friendUsername) {
            if (isset($this->users[$friendUsername])) {
                $friendUser = $this->users[$friendUsername];

                // Check if friendship already exists
                $exists = Friend::where('user_id', $freek->id)->where('friend_id', $friendUser->id)->exists()
                    || Friend::where('user_id', $friendUser->id)->where('friend_id', $freek->id)->exists();

                if (! $exists) {
                    Friend::create([
                        'user_id' => $freek->id,
                        'friend_id' => $friendUser->id,
                    ]);
                }
            }
        }

        $this->command->info('Created friendships');
    }

    private function createActiveGames(): void
    {
        $freek = $this->users['freek'];

        if (! $freek) {
            return;
        }

        // Game with jessica (nice board for screenshot - already created by ScreenshotGameSeeder)
        // Create additional active games
        $opponents = ['emmaw', 'jameschen', 'sophiem'];
        $lastMoveWords = ['BRIGHT', 'GARDEN', 'CASTLE'];

        foreach ($opponents as $index => $opponentUsername) {
            if (! isset($this->users[$opponentUsername])) {
                continue;
            }

            $opponent = $this->users[$opponentUsername];

            // Check if game already exists
            $existingGame = Game::where('status', GameStatus::Active)
                ->whereHas('players', fn ($q) => $q->where('user_id', $freek->id))
                ->whereHas('players', fn ($q) => $q->where('user_id', $opponent->id))
                ->first();

            if ($existingGame) {
                continue;
            }

            $isFreekTurn = $index % 2 === 0;
            $game = $this->createGameWithBoard(
                $freek,
                $opponent,
                GameStatus::Active,
                $isFreekTurn ? $freek : $opponent,
                rand(80, 150),
                rand(70, 140)
            );

            // Add a move so it shows the last played word instead of "Game Started!"
            $lastWord = $lastMoveWords[$index % count($lastMoveWords)];
            $lastMoveUser = $isFreekTurn ? $opponent : $freek;
            Move::create([
                'ulid' => strtolower(Str::ulid()->toString()),
                'game_id' => $game->id,
                'user_id' => $lastMoveUser->id,
                'type' => MoveType::Play,
                'tiles' => $this->wordToTiles($lastWord),
                'words' => [$lastWord],
                'score' => rand(15, 35),
            ]);

            $this->command->info("Created active game with {$opponentUsername}");
        }
    }

    private function createCompletedGames(): void
    {
        $freek = $this->users['freek'];

        if (! $freek) {
            return;
        }

        // Create completed games against 7 different opponents - freek always wins
        $opponents = ['oliverb', 'avaj', 'liamd', 'miag', 'noahm', 'emmaw', 'jameschen'];
        $lastWords = ['WINNER', 'GALAXY', 'BRIDGE', 'SILVER', 'MASTER', 'EXPERT', 'PLAYER'];

        foreach ($opponents as $index => $opponentUsername) {
            if (! isset($this->users[$opponentUsername])) {
                continue;
            }

            $opponent = $this->users[$opponentUsername];

            // Check if completed game already exists
            $existingGame = Game::where('status', GameStatus::Finished)
                ->whereHas('players', fn ($q) => $q->where('user_id', $freek->id))
                ->whereHas('players', fn ($q) => $q->where('user_id', $opponent->id))
                ->first();

            if ($existingGame) {
                continue;
            }

            // Freek always wins
            $freekScore = rand(280, 350);
            $opponentScore = rand(180, 260);

            $game = $this->createGameWithBoard(
                $freek,
                $opponent,
                GameStatus::Finished,
                null,
                $freekScore,
                $opponentScore,
                $freek // freek is always the winner
            );

            // Add a final move to show "Game Finished" style description
            $lastWord = $lastWords[$index % count($lastWords)];
            Move::create([
                'ulid' => strtolower(Str::ulid()->toString()),
                'game_id' => $game->id,
                'user_id' => $freek->id,
                'type' => MoveType::Play,
                'tiles' => $this->wordToTiles($lastWord),
                'words' => [$lastWord],
                'score' => rand(20, 45),
            ]);

            $this->command->info("Created completed game with {$opponentUsername} (freek won)");
        }
    }

    private function createPendingGames(): void
    {
        $freek = $this->users['freek'];

        if (! $freek) {
            return;
        }

        // Create a game where freek invited someone (pending acceptance)
        if (isset($this->users['miag'])) {
            $mia = $this->users['miag'];

            $existingGame = Game::where('status', GameStatus::Pending)
                ->whereHas('players', fn ($q) => $q->where('user_id', $freek->id))
                ->where('is_public', false)
                ->first();

            if (! $existingGame) {
                $tileBag = TileBag::forLanguage('en');

                $game = Game::create([
                    'ulid' => strtolower(Str::ulid()->toString()),
                    'language' => 'en',
                    'board_state' => app(Board::class)->createEmptyBoard(),
                    'board_template' => app(Board::class)->getBoardTemplate(),
                    'tile_bag' => $tileBag->toArray(),
                    'status' => GameStatus::Pending,
                    'current_turn_user_id' => null,
                    'is_public' => false,
                ]);

                GamePlayer::create([
                    'game_id' => $game->id,
                    'user_id' => $freek->id,
                    'rack_tiles' => [],
                    'score' => 0,
                    'turn_order' => 1,
                ]);

                $this->command->info('Created pending invitation game');
            }
        }
    }

    private function createPublicGames(): void
    {
        // Create public games from other users
        $publicGameCreators = ['noahm', 'miag', 'liamd', 'avaj'];

        foreach ($publicGameCreators as $creatorUsername) {
            if (! isset($this->users[$creatorUsername])) {
                continue;
            }

            $creator = $this->users[$creatorUsername];

            // Check if public game already exists from this user
            $existingGame = Game::where('status', GameStatus::Pending)
                ->where('is_public', true)
                ->whereHas('gamePlayers', fn ($q) => $q->where('user_id', $creator->id))
                ->first();

            if ($existingGame) {
                continue;
            }

            $tileBag = TileBag::forLanguage('en');

            $game = Game::create([
                'ulid' => strtolower(Str::ulid()->toString()),
                'language' => 'en',
                'board_state' => app(Board::class)->createEmptyBoard(),
                'board_template' => app(Board::class)->getBoardTemplate(),
                'tile_bag' => $tileBag->toArray(),
                'status' => GameStatus::Pending,
                'current_turn_user_id' => null,
                'is_public' => true,
            ]);

            GamePlayer::create([
                'game_id' => $game->id,
                'user_id' => $creator->id,
                'rack_tiles' => [],
                'score' => 0,
                'turn_order' => 1,
            ]);

            $this->command->info("Created public game from {$creatorUsername}");
        }
    }

    private function createGameWithBoard(
        User $player1,
        User $player2,
        GameStatus $status,
        ?User $currentTurn,
        int $score1,
        int $score2,
        ?User $winner = null
    ): Game {
        $tileBag = TileBag::forLanguage('en');
        $board = $this->createSimpleBoard();

        $game = Game::create([
            'ulid' => strtolower(Str::ulid()->toString()),
            'language' => 'en',
            'board_state' => $board,
            'board_template' => app(Board::class)->getBoardTemplate(),
            'tile_bag' => $this->getRemainingTiles(),
            'status' => $status,
            'current_turn_user_id' => $currentTurn?->id,
            'winner_id' => $winner?->id,
            'consecutive_passes' => 0,
        ]);

        GamePlayer::create([
            'game_id' => $game->id,
            'user_id' => $player1->id,
            'rack_tiles' => $this->getRandomRack(),
            'score' => $score1,
            'turn_order' => 1,
        ]);

        GamePlayer::create([
            'game_id' => $game->id,
            'user_id' => $player2->id,
            'rack_tiles' => $this->getRandomRack(),
            'score' => $score2,
            'turn_order' => 2,
        ]);

        return $game;
    }

    private function createSimpleBoard(): array
    {
        $board = app(Board::class)->createEmptyBoard();

        $points = [
            'A' => 1, 'B' => 3, 'C' => 3, 'D' => 2, 'E' => 1, 'F' => 4, 'G' => 2,
            'H' => 4, 'I' => 1, 'J' => 8, 'K' => 5, 'L' => 1, 'M' => 3, 'N' => 1,
            'O' => 1, 'P' => 3, 'Q' => 10, 'R' => 1, 'S' => 1, 'T' => 1, 'U' => 1,
            'V' => 4, 'W' => 4, 'X' => 8, 'Y' => 4, 'Z' => 10,
        ];

        // Simple words for other games
        $words = [
            ['word' => 'WORD', 'x' => 6, 'y' => 7, 'horizontal' => true],
            ['word' => 'GAME', 'x' => 6, 'y' => 7, 'horizontal' => false],
            ['word' => 'FUN', 'x' => 5, 'y' => 9, 'horizontal' => true],
        ];

        foreach ($words as $wordData) {
            $x = $wordData['x'];
            $y = $wordData['y'];

            foreach (str_split($wordData['word']) as $i => $letter) {
                $currentX = $wordData['horizontal'] ? $x + $i : $x;
                $currentY = $wordData['horizontal'] ? $y : $y + $i;

                if ($board[$currentY][$currentX] === null) {
                    $board[$currentY][$currentX] = [
                        'letter' => $letter,
                        'points' => $points[$letter] ?? 1,
                        'is_blank' => false,
                    ];
                }
            }
        }

        return $board;
    }

    private function getRemainingTiles(): array
    {
        return [
            ['letter' => 'A', 'points' => 1],
            ['letter' => 'E', 'points' => 1],
            ['letter' => 'I', 'points' => 1],
            ['letter' => 'O', 'points' => 1],
            ['letter' => 'N', 'points' => 1],
            ['letter' => 'T', 'points' => 1],
            ['letter' => 'R', 'points' => 1],
            ['letter' => 'S', 'points' => 1],
        ];
    }

    private function getRandomRack(): array
    {
        $letters = [
            ['letter' => 'A', 'points' => 1],
            ['letter' => 'E', 'points' => 1],
            ['letter' => 'I', 'points' => 1],
            ['letter' => 'O', 'points' => 1],
            ['letter' => 'N', 'points' => 1],
            ['letter' => 'T', 'points' => 1],
            ['letter' => 'R', 'points' => 1],
            ['letter' => 'S', 'points' => 1],
            ['letter' => 'L', 'points' => 1],
            ['letter' => 'D', 'points' => 2],
            ['letter' => 'G', 'points' => 2],
            ['letter' => 'B', 'points' => 3],
            ['letter' => 'M', 'points' => 3],
            ['letter' => 'P', 'points' => 3],
        ];

        shuffle($letters);

        $rack = array_slice($letters, 0, 7);

        return array_map(fn ($tile) => array_merge($tile, ['is_blank' => false]), $rack);
    }

    private function wordToTiles(string $word): array
    {
        $points = [
            'A' => 1, 'B' => 3, 'C' => 3, 'D' => 2, 'E' => 1, 'F' => 4, 'G' => 2,
            'H' => 4, 'I' => 1, 'J' => 8, 'K' => 5, 'L' => 1, 'M' => 3, 'N' => 1,
            'O' => 1, 'P' => 3, 'Q' => 10, 'R' => 1, 'S' => 1, 'T' => 1, 'U' => 1,
            'V' => 4, 'W' => 4, 'X' => 8, 'Y' => 4, 'Z' => 10,
        ];

        $tiles = [];
        foreach (str_split($word) as $index => $letter) {
            $tiles[] = [
                'letter' => $letter,
                'points' => $points[$letter] ?? 1,
                'x' => 7 + $index,
                'y' => 7,
                'is_blank' => false,
            ];
        }

        return $tiles;
    }
}
