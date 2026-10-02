<?php

namespace App\Domain\User\Commands;

use App\Domain\Game\Enums\GameStatus;
use App\Domain\Game\Models\Game;
use App\Domain\User\Models\User;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class CleanupInactiveGuestsCommand extends Command
{
    protected $signature = 'users:cleanup-inactive-guests';

    protected $description = 'Delete guest accounts that have been inactive for more than 60 days and have not played games with other players that are still around';

    public function handle(): int
    {
        $cutoffDate = now()->subDays(60);

        /*
         * Guests that played against someone else are kept, so the game history
         * of the other players (their games, moves and stats) stays intact.
         */
        $inactiveGuests = User::query()
            ->where('is_guest', true)
            ->where('updated_at', '<', $cutoffDate)
            ->whereDoesntHave('games', function (Builder $query): void {
                $query->whereIn('status', [GameStatus::Pending, GameStatus::Active]);
            })
            ->whereDoesntHave('games', function (Builder $query): void {
                $query->whereHas('gamePlayers', function (Builder $query): void {
                    $query->whereColumn('game_players.user_id', '!=', 'users.id');
                });
            })
            ->get();

        $count = $inactiveGuests->count();

        $inactiveGuests->each(fn (User $guest) => $this->deleteGuest($guest));

        $this->info("Deleted {$count} inactive guest accounts.");

        return self::SUCCESS;
    }

    protected function deleteGuest(User $guest): void
    {
        DB::transaction(function () use ($guest): void {
            $guest->games()->each(fn (Game $game) => $game->delete());

            $guest->delete();
        });
    }
}
