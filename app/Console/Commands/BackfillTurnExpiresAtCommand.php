<?php

namespace App\Console\Commands;

use App\Domain\Game\Enums\GameStatus;
use App\Domain\Game\Models\Game;
use Illuminate\Console\Command;

class BackfillTurnExpiresAtCommand extends Command
{
    protected $signature = 'games:backfill-turn-expires';

    protected $description = 'Backfill turn_expires_at for active games that are missing it';

    public function handle(): int
    {
        $games = Game::query()
            ->where('status', GameStatus::Active)
            ->whereNotNull('current_turn_user_id')
            ->whereNull('turn_expires_at')
            ->get();

        if ($games->isEmpty()) {
            $this->info('No games need backfilling.');

            return self::SUCCESS;
        }

        $this->info("Found {$games->count()} games to backfill.");

        $games->each(function (Game $game): void {
            $lastMove = $game->moves()->latest()->first();

            $expiresAt = $lastMove
                ? $lastMove->created_at->addHours(Game::turnTimeoutHours())
                : now()->addHours(Game::turnTimeoutHours());

            $game->update(['turn_expires_at' => $expiresAt]);

            $this->line("  - Game {$game->ulid}: expires at {$expiresAt}");
        });

        $this->info('Backfill complete.');

        return self::SUCCESS;
    }
}
