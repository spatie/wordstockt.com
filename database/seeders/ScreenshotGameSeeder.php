<?php

namespace Database\Seeders;

use App\Domain\Game\Enums\GameStatus;
use App\Domain\Game\Enums\MoveType;
use App\Domain\Game\Models\Game;
use App\Domain\Game\Models\GamePlayer;
use App\Domain\Game\Models\Move;
use App\Domain\Game\Support\Board;
use App\Domain\User\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ScreenshotGameSeeder extends Seeder
{
    public function run(): void
    {
        $freek = User::where('email', 'freek@spatie.be')->first();
        $jessica = User::where('email', 'jessica@spatie.be')->first();

        if (! $freek || ! $jessica) {
            $this->command->error('Users freek and jessica must exist. Run UserSeeder first.');

            return;
        }

        $board = $this->createNiceBoard();

        $game = Game::create([
            'ulid' => strtolower(Str::ulid()->toString()),
            'language' => 'en',
            'board_state' => $board,
            'board_template' => app(Board::class)->getBoardTemplate(),
            'tile_bag' => $this->getRemainingTiles(),
            'status' => GameStatus::Active,
            'current_turn_user_id' => $freek->id,
            'winner_id' => null,
            'consecutive_passes' => 0,
        ]);

        GamePlayer::create([
            'game_id' => $game->id,
            'user_id' => $freek->id,
            'rack_tiles' => [
                ['letter' => 'W', 'points' => 4, 'is_blank' => false],
                ['letter' => 'I', 'points' => 1, 'is_blank' => false],
                ['letter' => 'N', 'points' => 1, 'is_blank' => false],
                ['letter' => 'N', 'points' => 1, 'is_blank' => false],
                ['letter' => 'E', 'points' => 1, 'is_blank' => false],
                ['letter' => 'R', 'points' => 1, 'is_blank' => false],
                ['letter' => 'S', 'points' => 1, 'is_blank' => false],
            ],
            'score' => 156,
            'turn_order' => 1,
        ]);

        GamePlayer::create([
            'game_id' => $game->id,
            'user_id' => $jessica->id,
            'rack_tiles' => [
                ['letter' => 'O', 'points' => 1, 'is_blank' => false],
                ['letter' => 'L', 'points' => 3, 'is_blank' => false],
                ['letter' => 'D', 'points' => 2, 'is_blank' => false],
                ['letter' => 'E', 'points' => 1, 'is_blank' => false],
                ['letter' => 'K', 'points' => 3, 'is_blank' => false],
                ['letter' => 'U', 'points' => 2, 'is_blank' => false],
                ['letter' => 'M', 'points' => 3, 'is_blank' => false],
            ],
            'score' => 142,
            'turn_order' => 2,
        ]);

        // Add last move so it shows "jessica played REMOTE" instead of "Game Started!"
        Move::create([
            'ulid' => strtolower(Str::ulid()->toString()),
            'game_id' => $game->id,
            'user_id' => $jessica->id,
            'type' => MoveType::Play,
            'tiles' => [
                ['letter' => 'R', 'points' => 1, 'x' => 10, 'y' => 7, 'is_blank' => false],
                ['letter' => 'E', 'points' => 1, 'x' => 10, 'y' => 8, 'is_blank' => false],
                ['letter' => 'M', 'points' => 3, 'x' => 10, 'y' => 9, 'is_blank' => false],
                ['letter' => 'O', 'points' => 1, 'x' => 10, 'y' => 10, 'is_blank' => false],
                ['letter' => 'T', 'points' => 1, 'x' => 10, 'y' => 11, 'is_blank' => false],
                ['letter' => 'E', 'points' => 1, 'x' => 10, 'y' => 12, 'is_blank' => false],
            ],
            'words' => ['REMOTE'],
            'score' => 24,
        ]);

        $this->command->info("Screenshot game created: {$game->ulid}");
        $this->command->info("Freek's turn, score: 156 vs Jessica: 142");
    }

    private function createNiceBoard(): array
    {
        $board = app(Board::class)->createEmptyBoard();

        // English letter points
        $points = [
            'A' => 1, 'B' => 3, 'C' => 3, 'D' => 2, 'E' => 1, 'F' => 4, 'G' => 2,
            'H' => 4, 'I' => 1, 'J' => 8, 'K' => 5, 'L' => 1, 'M' => 3, 'N' => 1,
            'O' => 1, 'P' => 3, 'Q' => 10, 'R' => 1, 'S' => 1, 'T' => 1, 'U' => 1,
            'V' => 4, 'W' => 4, 'X' => 8, 'Y' => 4, 'Z' => 10,
        ];

        // Board with 6-letter words spread out, all intersections valid
        $words = [
            // Upper area - FRIEND horizontal with IGNORE vertical
            ['word' => 'FRIEND', 'x' => 7, 'y' => 2, 'horizontal' => true],
            ['word' => 'IGNORE', 'x' => 9, 'y' => 2, 'horizontal' => false], // shares I with FRIEND, E with GAMER

            // Center area - main words
            ['word' => 'GAMER', 'x' => 6, 'y' => 7, 'horizontal' => true], // shares E with IGNORE
            ['word' => 'GOAL', 'x' => 6, 'y' => 7, 'horizontal' => false],
            ['word' => 'PLAY', 'x' => 4, 'y' => 9, 'horizontal' => true],

            // Lower extension from GOAL
            ['word' => 'LAY', 'x' => 6, 'y' => 10, 'horizontal' => true],
            ['word' => 'YA', 'x' => 7, 'y' => 9, 'horizontal' => false],

            // Right side - REMOTE vertical extending from R of GAMER
            ['word' => 'REMOTE', 'x' => 10, 'y' => 7, 'horizontal' => false], // shares R with GAMER
        ];

        foreach ($words as $wordData) {
            $x = $wordData['x'];
            $y = $wordData['y'];

            foreach (str_split($wordData['word']) as $i => $letter) {
                $currentX = $wordData['horizontal'] ? $x + $i : $x;
                $currentY = $wordData['horizontal'] ? $y : $y + $i;

                // Only place if cell is empty (avoid overwriting shared letters)
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
            ['letter' => 'A', 'points' => 1],
            ['letter' => 'E', 'points' => 1],
            ['letter' => 'E', 'points' => 1],
            ['letter' => 'I', 'points' => 1],
            ['letter' => 'O', 'points' => 1],
            ['letter' => 'N', 'points' => 1],
            ['letter' => 'T', 'points' => 1],
            ['letter' => 'R', 'points' => 2],
            ['letter' => 'B', 'points' => 3],
            ['letter' => 'G', 'points' => 3],
            ['letter' => 'Z', 'points' => 4],
        ];
    }
}
