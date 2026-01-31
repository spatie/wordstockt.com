<?php

namespace Database\Seeders;

use App\Domain\Game\Models\Game;
use App\Domain\User\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class LeaderboardSeeder extends Seeder
{
    public function run(): void
    {
        $players = $this->createPlayers();

        $this->createGames($players);
    }

    private function createPlayers(): array
    {
        $playerData = [
            ['username' => 'champion', 'elo_rating' => 1850, 'avatar_color' => '#E74C3C'],
            ['username' => 'wordmaster', 'elo_rating' => 1720, 'avatar_color' => '#3498DB'],
            ['username' => 'lexicon', 'elo_rating' => 1680, 'avatar_color' => '#2ECC71'],
            ['username' => 'scrabbler', 'elo_rating' => 1550, 'avatar_color' => '#9B59B6'],
            ['username' => 'wordsmith', 'elo_rating' => 1480, 'avatar_color' => '#F39C12'],
            ['username' => 'letterking', 'elo_rating' => 1420, 'avatar_color' => '#1ABC9C'],
            ['username' => 'vocabulist', 'elo_rating' => 1350, 'avatar_color' => '#E67E22'],
            ['username' => 'spellbound', 'elo_rating' => 1280, 'avatar_color' => '#34495E'],
            ['username' => 'alphabeta', 'elo_rating' => 1220, 'avatar_color' => '#16A085'],
            ['username' => 'newplayer', 'elo_rating' => 1200, 'avatar_color' => '#95A5A6'],
        ];

        $players = [];

        foreach ($playerData as $data) {
            $players[$data['username']] = User::factory()->create([
                'username' => $data['username'],
                'email' => $data['username'].'@test.com',
                'elo_rating' => $data['elo_rating'],
                'avatar_color' => $data['avatar_color'],
                'games_played' => 0,
                'games_won' => 0,
            ]);
        }

        return $players;
    }

    private function createGames(array $players): void
    {
        // Games won in the last 7 days (recent)
        $this->createWins($players['champion'], 8, Carbon::now()->subDays(3));
        $this->createWins($players['wordmaster'], 6, Carbon::now()->subDays(5));
        $this->createWins($players['lexicon'], 5, Carbon::now()->subDays(2));
        $this->createWins($players['scrabbler'], 4, Carbon::now()->subDays(6));

        // Games won 8-30 days ago (still in monthly)
        $this->createWins($players['champion'], 4, Carbon::now()->subDays(15));
        $this->createWins($players['wordsmith'], 7, Carbon::now()->subDays(20));
        $this->createWins($players['letterking'], 5, Carbon::now()->subDays(25));
        $this->createWins($players['vocabulist'], 3, Carbon::now()->subDays(18));

        // Games won 31-365 days ago (only in yearly, not monthly)
        $this->createWins($players['spellbound'], 15, Carbon::now()->subDays(60));
        $this->createWins($players['alphabeta'], 12, Carbon::now()->subDays(90));
        $this->createWins($players['champion'], 10, Carbon::now()->subDays(120));
        $this->createWins($players['wordmaster'], 8, Carbon::now()->subDays(180));
        $this->createWins($players['newplayer'], 6, Carbon::now()->subDays(200));

        // Games won more than 365 days ago (won't appear in time-based leaderboards)
        $this->createWins($players['lexicon'], 20, Carbon::now()->subDays(400));
        $this->createWins($players['scrabbler'], 15, Carbon::now()->subDays(500));
    }

    private function createWins(User $winner, int $count, Carbon $around): void
    {
        // Get a random opponent (any other user)
        $opponent = User::where('id', '!=', $winner->id)->inRandomOrder()->first();

        for ($i = 0; $i < $count; $i++) {
            // Randomize the date a bit around the target date
            $gameDate = $around->copy()->addHours(random_int(-48, 48));

            $game = Game::factory()->finished()->create([
                'winner_id' => $winner->id,
                'updated_at' => $gameDate,
                'created_at' => $gameDate->copy()->subHours(random_int(1, 72)),
            ]);

            // Attach both players to the game
            $game->players()->attach([
                $winner->id => [
                    'score' => random_int(250, 400),
                    'turn_order' => 1,
                    'rack_tiles' => '[]',
                ],
                $opponent->id => [
                    'score' => random_int(150, 280),
                    'turn_order' => 2,
                    'rack_tiles' => '[]',
                ],
            ]);
        }

        // Update winner's stats
        $winner->increment('games_played', $count);
        $winner->increment('games_won', $count);

        // Update opponent's stats (they lost these games)
        $opponent->increment('games_played', $count);
    }
}
